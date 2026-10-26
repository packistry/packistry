<?php

declare(strict_types=1);

use App\Enums\MirrorStatus;
use App\Enums\Permission;
use App\Enums\TokenAbility;
use App\Enums\UpstreamCredentialType;
use App\Models\Package;
use App\Models\Upstream;
use App\Models\Version;
use Composer\MetadataMinifier\MetadataMinifier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\travel;

beforeEach(function (): void {
    Storage::fake();
    Http::preventStrayRequests();
});

it('returns 404 when the repository has no upstreams', function (): void {
    $repository = repository(public: true);

    getJson($repository->url('/p2/acme/package.json'))
        ->assertNotFound();

    Http::assertNothingSent();
});

it('mirrors package metadata from the upstream', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [
            upstreamVersion('2.0.0', 'bbb'),
            upstreamVersion('2.0.0-beta1', 'aaa'),
            upstreamVersion('1.0.0', 'aaa'),
        ]), headers: ['Last-Modified' => 'Wed, 21 Oct 2026 07:28:00 GMT']),
    ]);

    $response = getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('minified', 'composer/2.0');

    $versions = MetadataMinifier::expand($response->json('packages')['acme/package']);

    expect(array_column(array_column($versions, 'dist'), 'url'))->toBe([
        $repository->url('/_mirror/acme/package/bbb'),
        $repository->url('/_mirror/acme/package/aaa'),
        $repository->url('/_mirror/acme/package/aaa'),
    ]);

    /** @var Package $package */
    $package = Package::query()->sole();

    expect($package)
        ->repository_id->toBe($repository->id)
        ->name->toBe('acme/package')
        ->upstream_id->toBe($upstream->id)
        ->mirror_status->toBe(MirrorStatus::OK)
        ->type->toBe('library')
        ->description->toBe('An upstream package')
        ->latest_version->toBe('2.0.0');

    expect($package->versions()->count())->toBe(0);
});

it('serves local packages before upstreams', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Package::factory()
        ->for($repository)
        ->name('acme/package')
        ->has(Version::factory()->state(['name' => '1.0.0']))
        ->create();

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    Http::assertNothingSent();
});

it('caches metadata and revalidates it after the ttl', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    $metadata = upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')]);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::sequence()
            ->push($metadata, headers: ['Last-Modified' => 'Wed, 21 Oct 2026 07:28:00 GMT'])
            ->push(null, 304),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();
    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    Http::assertSentCount(2);

    travel(301)->seconds();

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://repo.packagist.org/p2/acme/package.json'
        && $request->header('If-Modified-Since') === ['Wed, 21 Oct 2026 07:28:00 GMT']);
});

it('serves stale metadata when the upstream is down', function (Closure $failure): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::sequence()
            ->push(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')]))
            ->pushResponse($failure()),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    travel(301)->seconds();

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    expect($upstream->refresh()->last_error)->not->toBeNull();
})->with([
    'server error' => fn (): Closure => fn () => Http::response('down', 503),
    'connection error' => fn (): Closure => fn () => Http::failedConnection(),
]);

it('clears the last error when the upstream answers again', function (): void {
    $upstream = Upstream::factory()->packagist()->create([
        'last_error' => 'Connection refused',
        'last_error_at' => now(),
        'metadata_url' => 'https://repo.packagist.org/p2/%package%.json',
        'root_checked_at' => now(),
    ]);
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')])),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    expect($upstream->refresh())
        ->last_error->toBeNull()
        ->last_error_at->toBeNull();
});

it('skips an upstream that recently failed', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/*' => Http::failedConnection(),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();
    getJson($repository->url('/p2/acme/other.json'))->assertNotFound();

    // the second request does not wait for the upstream again
    Http::assertSentCount(2);
});

it('returns 404 without caching when the upstream is unreachable', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/*' => Http::failedConnection(),
    ]);

    getJson($repository->url('/p2/acme/package.json'))
        ->assertNotFound();

    expect(Package::query()->count())->toBe(0);
});

it('caches upstream not found responses', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/*' => Http::response(null, 404),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();
    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();

    Http::assertSentCount(2);

    travel(601)->seconds();

    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();

    Http::assertSentCount(3);
});

it('skips upstreams whose filters do not match', function (): void {
    $gitlab = Upstream::factory()
        ->url('https://gitlab.test/api/v4/group/1/-/packages/composer/packages.json')
        ->filters(only: ['acme/*'])
        ->create();
    $packagist = Upstream::factory()->packagist()->filters(exclude: ['acme/*'])->create();

    $repository = mirroredRepository([$gitlab, $packagist], public: true);

    Http::fake([
        'gitlab.test/api/v4/group/1/-/packages/composer/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'gitlab.test/p2/*' => Http::response(null, 404),
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/vendor/package.json' => Http::response(upstreamMetadata('vendor/package', [upstreamVersion('1.0.0', 'aaa')])),
    ]);

    getJson($repository->url('/p2/vendor/package.json'))->assertOk();
    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();

    // never asks the private upstream about public names, nor packagist about private names
    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'gitlab.test/p2/vendor'));
    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'packagist.org/p2/acme'));
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://gitlab.test/p2/acme/package.json');
});

it('uses the first upstream that has the package', function (): void {
    $gitlab = Upstream::factory()->url('https://gitlab.test/api/v4/group/1/-/packages/composer/packages.json')->create();
    $packagist = Upstream::factory()->packagist()->create();

    $repository = mirroredRepository([$gitlab, $packagist], public: true);

    Http::fake([
        'gitlab.test/api/v4/group/1/-/packages/composer/packages.json' => Http::response([
            'metadata-url' => '/api/v4/group/1/-/packages/composer/p2/%package%.json',
        ]),
        'gitlab.test/api/v4/group/1/-/packages/composer/p2/acme/package.json' => Http::response(
            upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'private', 'https://gitlab.test/archive.zip')], minified: false)
        ),
    ]);

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.dist.url', $repository->url('/_mirror/acme/package/private'));

    expect(Package::query()->sole()->upstream_id)->toBe($gitlab->id);

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'packagist'));
});

it('does not fall through to a lower priority upstream when a higher one is unavailable', function (): void {
    $gitlab = Upstream::factory()->url('https://gitlab.test/packages.json')->create();
    $packagist = Upstream::factory()->packagist()->create();

    $repository = mirroredRepository([$gitlab, $packagist], public: true);

    Http::fake([
        'gitlab.test/*' => Http::failedConnection(),
        'repo.packagist.org/*' => Http::response(['metadata-url' => '/p2/%package%.json']),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();

    expect(Package::query()->count())->toBe(0);

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'packagist'));
});

it('keeps a package with its upstream when that upstream no longer has it', function (): void {
    $gitlab = Upstream::factory()->url('https://gitlab.test/packages.json')->create();
    $packagist = Upstream::factory()->packagist()->create();

    $repository = mirroredRepository([$gitlab, $packagist], public: true);

    Http::fake([
        'gitlab.test/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'gitlab.test/p2/acme/package.json' => Http::sequence()
            ->push(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'private')]))
            ->push(null, 404)
            ->push(upstreamMetadata('acme/package', [upstreamVersion('1.1.0', 'private')])),
        'repo.packagist.org/*' => Http::response(upstreamMetadata('acme/package', [upstreamVersion('3.0.0', 'public')])),
    ]);

    getJson($repository->url('/p2/acme/package.json'))
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    travel(301)->seconds();

    // served while the upstream is revalidated in the background
    getJson($repository->url('/p2/acme/package.json'))
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    // a public upstream must not take over the name, the 404 may be an expired token
    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();

    /** @var Package $package */
    $package = Package::query()->sole();

    expect($package)
        ->upstream_id->toBe($gitlab->id)
        ->mirror_status->toBe(MirrorStatus::MISSING);

    getJson($repository->url('/list.json'))->assertJsonPath('packageNames', []);

    // it comes back once its upstream has it again
    travel(601)->seconds();

    getJson($repository->url('/p2/acme/package.json'))
        ->assertJsonPath('packages.acme/package.0.version', '1.1.0');

    expect($package->refresh()->mirror_status)->toBe(MirrorStatus::OK);

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'packagist'));
});

it('keeps a package with its upstream when only dev metadata is requested', function (): void {
    $gitlab = Upstream::factory()->url('https://gitlab.test/packages.json')->create();
    $packagist = Upstream::factory()->packagist()->create();

    $repository = mirroredRepository([$gitlab, $packagist], public: true);

    Http::fake([
        'gitlab.test/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'gitlab.test/p2/acme/package.json' => Http::sequence()
            ->push(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'private')]))
            ->push(null, 404),
        'gitlab.test/p2/acme/package~dev.json' => Http::response(null, 404),
        'repo.packagist.org/*' => Http::response(upstreamMetadata('acme/package', [upstreamVersion('dev-main', 'main')])),
    ]);

    getJson($repository->url('/p2/acme/package.json'))
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    travel(301)->seconds();

    // the first request revalidates the cached stable metadata in the background
    getJson($repository->url('/p2/acme/package~dev.json'))->assertNotFound();
    getJson($repository->url('/p2/acme/package~dev.json'))->assertNotFound();

    expect(Package::query()->sole())
        ->upstream_id->toBe($gitlab->id)
        ->mirror_status->toBe(MirrorStatus::MISSING);

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'packagist'));
});

it('only moves a missing package to another upstream when an admin refreshes it', function (): void {
    user([Permission::PACKAGE_UPDATE, Permission::UNSCOPED]);

    $gitlab = Upstream::factory()->url('https://gitlab.test/packages.json')->create();
    $packagist = Upstream::factory()->packagist()->create();

    $repository = mirroredRepository([$gitlab, $packagist], public: true);

    $package = Package::factory()
        ->for($repository)
        ->name('acme/package')
        ->state([
            'upstream_id' => $gitlab->id,
            'mirror_status' => MirrorStatus::MISSING,
        ])
        ->create();

    Http::fake([
        'gitlab.test/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'gitlab.test/p2/*' => Http::response(null, 404),
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::response(
            upstreamMetadata('acme/package', [upstreamVersion('3.0.0', 'public')])
        ),
    ]);

    postJson("/api/packages/$package->id/refresh")->assertOk();

    expect($package->refresh())
        ->upstream_id->toBe($packagist->id)
        ->mirror_status->toBe(MirrorStatus::OK);
});

it('only serves the requested package from upstream metadata', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    $metadata = upstreamMetadata('evil/package', [upstreamVersion('1.0.0', 'aaa')]);
    $metadata['packages']['acme/private'] = [upstreamVersion('9.9.9', 'bbb')];
    $metadata['security-advisories'] = [];

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/evil/package.json' => Http::response($metadata),
    ]);

    getJson($repository->url('/p2/evil/package.json'))
        ->assertOk()
        ->assertJsonMissingPath('packages.acme/private')
        ->assertJsonMissingPath('security-advisories')
        ->assertJsonPath('packages.evil/package.0.version', '1.0.0');
});

it('keeps serving cached metadata when the upstream answers 404 because its credentials stopped working', function (): void {
    $upstream = Upstream::factory()->url('https://gitlab.test/packages.json')->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'gitlab.test/packages.json' => Http::sequence()
            ->push(['metadata-url' => '/p2/%package%.json'])
            ->push(null, 401),
        'gitlab.test/p2/acme/package.json' => Http::sequence()
            ->push(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')]))
            ->push(null, 404),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    travel(301)->seconds();

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    expect(Package::query()->sole())
        ->mirror_status->toBe(MirrorStatus::OK)
        ->and($upstream->refresh()->last_error)->toContain('HTTP 401');
});

it('resolves dev-only packages through the dev metadata', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::response(null, 404),
        'repo.packagist.org/p2/acme/package~dev.json' => Http::response(upstreamMetadata('acme/package', [upstreamVersion('dev-main', 'ccc')])),
    ]);

    getJson($repository->url('/p2/acme/package~dev.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', 'dev-main');

    expect(Package::query()->sole())
        ->latest_version->toBeNull();
});

it('only sends credentials to their own host', function (): void {
    $gitlab = Upstream::factory()
        ->url('https://gitlab.test/packages.json')
        ->filters(only: ['acme/*'])
        ->withCredential('gitlab.test', UpstreamCredentialType::HTTP_BASIC, 'deploy', 'gitlab-secret')
        ->create();
    $packagist = Upstream::factory()
        ->packagist()
        ->withCredential('api.github.com', UpstreamCredentialType::BEARER, null, 'github-token')
        ->create();

    $repository = mirroredRepository([$gitlab, $packagist]);
    deployToken(TokenAbility::REPOSITORY_READ, withAccess: true);

    Http::fake([
        'gitlab.test/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'gitlab.test/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')])),
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/vendor/package.json' => Http::response(upstreamMetadata('vendor/package', [upstreamVersion('1.0.0', 'bbb')])),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();
    getJson($repository->url('/p2/vendor/package.json'))->assertOk();

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'gitlab.test')
        && $request->header('Authorization') === ['Basic '.base64_encode('deploy:gitlab-secret')]);

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'packagist')
        && $request->hasHeader('Authorization'));
});

it('discovers relative metadata urls', function (string $url, string $metadataUrl, string $expected): void {
    $upstream = Upstream::factory()->url($url)->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        '*/packages.json' => Http::response(['metadata-url' => $metadataUrl]),
        $expected => Http::response(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')])),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    expect($upstream->refresh()->metadata_url)->toBe(str_replace('acme/package', '%package%', $expected));
})->with([
    'absolute path' => [
        'https://gitlab.test/api/v4/group/1/-/packages/composer/packages.json',
        '/api/v4/group/1/-/packages/composer/p2/%package%.json',
        'https://gitlab.test/api/v4/group/1/-/packages/composer/p2/acme/package.json',
    ],
    'relative path' => [
        'https://satis.test/sub',
        'p2/%package%.json',
        'https://satis.test/sub/p2/acme/package.json',
    ],
    'full url' => [
        'https://repo.test',
        'https://cdn.test/p2/%package%.json',
        'https://cdn.test/p2/acme/package.json',
    ],
]);

it('does not mirror from upstreams without a composer 2 metadata url', function (): void {
    $upstream = Upstream::factory()->url('https://legacy.test')->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'legacy.test/packages.json' => Http::response(['providers-url' => '/p/%package%$%hash%.json']),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();

    expect($upstream->refresh()->last_error)->toContain('metadata-url');
});

it('excludes gone packages from list and search', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Package::factory()->for($repository)->name('acme/ok')->state(['upstream_id' => $upstream->id, 'mirror_status' => MirrorStatus::OK])->create();
    Package::factory()->for($repository)->name('acme/gone')->state(['upstream_id' => $upstream->id, 'mirror_status' => MirrorStatus::GONE])->create();

    getJson($repository->url('/list.json'))
        ->assertExactJson(['packageNames' => ['acme/ok']]);

    getJson($repository->url('/search.json?q=acme'))
        ->assertJsonPath('total', 1);
});
