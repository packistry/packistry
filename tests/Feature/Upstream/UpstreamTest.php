<?php

declare(strict_types=1);

use App\Enums\MirrorStatus;
use App\Enums\Permission;
use App\Enums\UpstreamCredentialType;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Upstream;
use App\Models\UpstreamCredential;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('indexes', function (?User $user, int $status): void {
    Upstream::factory()
        ->withCredential('gitlab.test')
        ->count(3)
        ->create();

    $response = getJson('/api/upstreams')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.credentials.0.host', 'gitlab.test')
        ->assertJsonMissingPath('data.0.credentials.0.secret');
})->with(guestAndUsers(Permission::UPSTREAM_READ));

it('shows', function (?User $user, int $status): void {
    $upstream = Upstream::factory()->create();

    getJson("/api/upstreams/$upstream->id")
        ->assertStatus($status);
})->with(guestAndUsers(Permission::UPSTREAM_READ));

it('stores', function (?User $user, int $status): void {
    $response = postJson('/api/upstreams', [
        'name' => 'GitLab group',
        'url' => 'https://gitlab.test/api/v4/group/1/-/packages/composer/packages.json/',
        'only' => ['acme/*', ' '],
        'credentials' => [
            ['host' => 'GitLab.test', 'type' => 'http-basic', 'username' => 'deploy', 'secret' => 'gitlab-secret'],
        ],
    ])->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    /** @var Upstream $upstream */
    $upstream = Upstream::query()->with('credentials')->sole();

    expect($upstream)
        ->url->toBe('https://gitlab.test/api/v4/group/1/-/packages/composer/packages.json')
        ->only->toBe(['acme/*'])
        ->exclude->toBeNull()
        ->isPrivate()->toBeTrue();

    /** @var UpstreamCredential $credential */
    $credential = $upstream->credentials->sole();

    expect($credential)
        ->host->toBe('gitlab.test')
        ->type->toBe(UpstreamCredentialType::HTTP_BASIC)
        ->username->toBe('deploy')
        ->secret->toBe('gitlab-secret');

    // stored encrypted
    expect(DB::table('upstream_credentials')->value('secret'))->not->toContain('gitlab-secret');

    $response
        ->assertJsonPath('private', true)
        ->assertJsonPath('credentials.0.username', 'deploy')
        ->assertJsonMissingPath('credentials.0.secret');

    expect($response->getContent())->not->toContain('gitlab-secret');
})->with(guestAndUsers(Permission::UPSTREAM_CREATE, userWithPermission: 201));

it('validates credentials', function (array $credential, string $error): void {
    user(Permission::UPSTREAM_CREATE);

    postJson('/api/upstreams', [
        'name' => 'GitLab group',
        'url' => 'https://gitlab.test/packages.json',
        'credentials' => [$credential],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$error]);
})->with([
    'missing secret' => [['host' => 'gitlab.test', 'type' => 'bearer'], 'credentials.0.secret'],
    'missing username' => [['host' => 'gitlab.test', 'type' => 'http-basic', 'secret' => 's'], 'credentials.0.username'],
    'invalid type' => [['host' => 'gitlab.test', 'type' => 'magic', 'secret' => 's'], 'credentials.0.type'],
]);

it('updates and keeps secrets that are not resent', function (?User $user, int $status): void {
    $upstream = Upstream::factory()
        ->withCredential('gitlab.test', UpstreamCredentialType::HTTP_BASIC, 'deploy', 'old-secret')
        ->withCredential('api.github.com', UpstreamCredentialType::BEARER, null, 'github-token')
        ->create(['metadata_url' => 'https://gitlab.test/p2/%package%.json']);

    /** @var UpstreamCredential $gitlab */
    $gitlab = $upstream->credentials()->where('host', 'gitlab.test')->sole();

    putJson("/api/upstreams/$upstream->id", [
        'name' => 'Renamed',
        'url' => 'https://gitlab.test/other/packages.json',
        'credentials' => [
            ['id' => $gitlab->id, 'host' => 'gitlab.test', 'type' => 'http-basic', 'username' => 'deploy2'],
            ['host' => 'files.test', 'type' => 'header', 'username' => 'PRIVATE-TOKEN', 'secret' => 'header-secret'],
        ],
    ])->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $upstream->refresh()->load('credentials');

    expect($upstream)
        ->name->toBe('Renamed')
        ->metadata_url->toBeNull()
        ->and($upstream->credentials->pluck('host')->sort()->values()->all())->toBe(['files.test', 'gitlab.test']);

    /** @var UpstreamCredential $updated */
    $updated = $upstream->credentials->firstWhere('host', 'gitlab.test');

    expect($updated)
        ->id->toBe($gitlab->id)
        ->username->toBe('deploy2')
        ->secret->toBe('old-secret');
})->with(guestAndUsers(Permission::UPSTREAM_UPDATE));

it('does not allow authenticating an upstream that is attached to a public repository', function (): void {
    user(Permission::UPSTREAM_UPDATE);

    $upstream = Upstream::factory()->url('https://gitlab.test/packages.json')->create();
    mirroredRepository($upstream, public: true);

    putJson("/api/upstreams/$upstream->id", [
        'name' => $upstream->name,
        'url' => $upstream->url,
        'credentials' => [
            ['host' => 'gitlab.test', 'type' => 'bearer', 'secret' => 'secret'],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['credentials']);

    expect($upstream->credentials()->count())->toBe(0);

    // credentials for other hosts (e.g. dists) are fine
    putJson("/api/upstreams/$upstream->id", [
        'name' => $upstream->name,
        'url' => $upstream->url,
        'credentials' => [
            ['host' => 'api.github.com', 'type' => 'bearer', 'secret' => 'secret'],
        ],
    ])->assertOk();
});

it('destroys and keeps mirrored packages as gone', function (?User $user, int $status): void {
    Storage::fake();

    $upstream = Upstream::factory()->create();
    $repository = mirroredRepository($upstream);
    $package = Package::factory()
        ->for($repository)
        ->state(['upstream_id' => $upstream->id, 'mirror_status' => MirrorStatus::OK])
        ->create();

    Storage::put($upstream->storagePath('dists/acme/package/aaa.zip'), 'zip');

    deleteJson("/api/upstreams/$upstream->id")
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    expect(Upstream::query()->count())->toBe(0)
        ->and($package->refresh())
        ->upstream_id->toBeNull()
        ->mirror_status->toBe(MirrorStatus::GONE);

    Storage::assertMissing($upstream->storagePath('dists/acme/package/aaa.zip'));
})->with(guestAndUsers(Permission::UPSTREAM_DELETE));

it('tests the connection', function (): void {
    user(Permission::UPSTREAM_UPDATE);
    Http::preventStrayRequests();

    $working = Upstream::factory()->url('https://repo.test')->create();
    $legacy = Upstream::factory()->url('https://legacy.test')->create();

    Http::fake([
        'repo.test/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'legacy.test/packages.json' => Http::response(['providers-url' => '/p/%package%.json']),
    ]);

    postJson("/api/upstreams/$working->id/test")
        ->assertOk()
        ->assertExactJson(['ok' => true, 'metadata_url' => 'https://repo.test/p2/%package%.json', 'error' => null]);

    postJson("/api/upstreams/$legacy->id/test")
        ->assertOk()
        ->assertJsonPath('ok', false);
});

it('sets repository upstreams in priority order', function (?User $user, int $status): void {
    $repository = Repository::factory()->create();
    [$first, $second, $third] = Upstream::factory()->count(3)->create()->all();

    putJson("/api/repositories/$repository->id/upstreams", [
        'upstreams' => [
            ['id' => $second->id],
            ['id' => $first->id, 'enabled' => false],
        ],
    ])->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $upstreams = $repository->upstreams()->get();

    expect($upstreams->pluck('id')->all())->toBe([$second->id, $first->id])
        ->and($upstreams->map(fn (Upstream $upstream) => $upstream->pivot?->enabled)->all())->toBe([true, false]);
})->with([
    ...guestAndUsers([Permission::REPOSITORY_UPDATE, Permission::UNSCOPED]),
]);

it('marks packages of removed upstreams as gone', function (): void {
    user([Permission::REPOSITORY_UPDATE, Permission::UNSCOPED]);

    [$kept, $removed] = Upstream::factory()->count(2)->create()->all();
    $repository = mirroredRepository([$kept, $removed]);

    $keptPackage = Package::factory()->for($repository)->state(['upstream_id' => $kept->id, 'mirror_status' => MirrorStatus::OK])->create();
    $removedPackage = Package::factory()->for($repository)->state(['upstream_id' => $removed->id, 'mirror_status' => MirrorStatus::OK])->create();

    putJson("/api/repositories/$repository->id/upstreams", [
        'upstreams' => [['id' => $kept->id]],
    ])->assertOk();

    expect($keptPackage->refresh()->mirror_status)->toBe(MirrorStatus::OK)
        ->and($removedPackage->refresh()->mirror_status)->toBe(MirrorStatus::GONE);
});

it('does not attach authenticated upstreams to public repositories', function (): void {
    user([Permission::REPOSITORY_UPDATE, Permission::UNSCOPED]);

    $repository = Repository::factory()->public()->create();
    $private = Upstream::factory()->url('https://gitlab.test/packages.json')->withCredential('gitlab.test')->create();
    $packagist = Upstream::factory()->packagist()->withCredential('api.github.com', UpstreamCredentialType::BEARER, null)->create();

    putJson("/api/repositories/$repository->id/upstreams", [
        'upstreams' => [['id' => $private->id]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upstreams']);

    putJson("/api/repositories/$repository->id/upstreams", [
        'upstreams' => [['id' => $packagist->id]],
    ])->assertOk();
});

it('does not make repositories with authenticated upstreams public', function (): void {
    user([Permission::REPOSITORY_UPDATE, Permission::UNSCOPED]);

    $private = Upstream::factory()->url('https://gitlab.test/packages.json')->withCredential('gitlab.test')->create();
    $repository = mirroredRepository($private);

    putJson("/api/repositories/$repository->id", [
        'name' => $repository->name,
        'path' => $repository->path,
        'public' => true,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['public']);
});
