<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\TokenAbility;
use App\Enums\UpstreamCredentialType;
use App\Mirror\HostResolver;
use App\Models\MirrorDist;
use App\Models\Package;
use App\Models\Upstream;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\FakeHostResolver;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    Storage::fake();
    Http::preventStrayRequests();
});

function dns(): FakeHostResolver
{
    /** @var FakeHostResolver $resolver */
    $resolver = app(HostResolver::class);

    return $resolver;
}

/**
 * Blocked requests are recorded by the http fake before they are rejected, but never get a response.
 */
function reachedHost(string $host): bool
{
    return Http::recorded(fn (Request $request, mixed $response): bool => str_contains($request->url(), $host) && $response !== null)
        ->isNotEmpty();
}

function zipContent(): string
{
    return (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip');
}

it('blocks upstreams that resolve to private or reserved addresses', function (string $address): void {
    dns()->map('internal.test', $address);

    $upstream = Upstream::factory()->url('https://internal.test')->create();
    $repository = mirroredRepository($upstream, public: true);

    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();

    Http::assertNothingSent();

    expect($upstream->refresh()->last_error)
        ->toContain('Blocked request to internal.test')
        ->toContain('MIRROR_ALLOWED_HOSTS');
})->with([
    'loopback' => '127.0.0.1',
    'private network' => '10.0.0.5',
    'cloud metadata' => '169.254.169.254',
    'carrier-grade nat' => '100.64.0.1',
    'ipv6 loopback' => '::1',
    'ipv4 mapped ipv6' => '::ffff:127.0.0.1',
]);

it('blocks ip literals in upstream urls', function (): void {
    $upstream = Upstream::factory()->url('http://169.254.169.254/latest')->create();
    $repository = mirroredRepository($upstream, public: true);

    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();

    Http::assertNothingSent();
});

it('blocks a metadata-url that points to an internal address', function (): void {
    $upstream = Upstream::factory()->url('https://repo.test')->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.test/packages.json' => Http::response(['metadata-url' => 'http://127.0.0.1:6379/%package%.json']),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();

    expect(reachedHost('127.0.0.1'))->toBeFalse()
        ->and($upstream->refresh()->last_error)->toContain('Blocked request to 127.0.0.1');
});

it('blocks redirects to internal addresses', function (): void {
    $upstream = Upstream::factory()->url('https://repo.test')->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.test/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.test/p2/acme/package.json' => Http::response(null, 302, ['Location' => 'http://169.254.169.254/latest/meta-data']),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();

    expect(reachedHost('169.254.169.254'))->toBeFalse()
        ->and($upstream->refresh()->last_error)->toContain('Blocked request to 169.254.169.254');
});

it('blocks dists that point to internal addresses', function (): void {
    $upstream = Upstream::factory()->url('https://repo.test')->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.test/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.test/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [
            upstreamVersion('1.0.0', 'aaa', 'http://10.0.0.5/secret.zip'),
        ])),
        'repo.test/p2/acme/package~dev.json' => Http::response(null, 404),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();
    get($repository->url('/_mirror/acme/package/aaa'))->assertStatus(502);

    expect(reachedHost('10.0.0.5'))->toBeFalse()
        ->and(MirrorDist::query()->count())->toBe(0);
});

it('allows internal hosts that are explicitly allowed', function (string $allowed): void {
    config()->set('mirror.allowed_hosts', $allowed);
    dns()->map('gitlab.internal', '10.0.0.5');

    $upstream = Upstream::factory()->url('https://gitlab.internal')->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'gitlab.internal/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'gitlab.internal/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')])),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();
})->with([
    'listed host' => 'other.internal, GitLab.internal',
    'every host' => '*',
]);

it('only mirrors zip dists', function (): void {
    $upstream = Upstream::factory()->url('https://repo.test')->create();
    $repository = mirroredRepository($upstream, public: true);

    $version = upstreamVersion('1.0.0', 'aaa', 'https://repo.test/dist/package.tar');
    $version['dist']['type'] = 'tar';

    Http::fake([
        'repo.test/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.test/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [$version])),
        'repo.test/p2/acme/package~dev.json' => Http::response(null, 404),
        'repo.test/dist/*' => Http::response('anything at all'),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();
    get($repository->url('/_mirror/acme/package/aaa'))->assertStatus(502);

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/dist/'));
    expect(MirrorDist::query()->count())->toBe(0);
});

it('verifies zip dists even when the upstream claims they are zips', function (): void {
    $upstream = Upstream::factory()->url('https://repo.test')->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.test/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.test/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [
            upstreamVersion('1.0.0', 'aaa', 'https://repo.test/dist/package.zip'),
        ])),
        'repo.test/p2/acme/package~dev.json' => Http::response(null, 404),
        'repo.test/dist/*' => Http::response('{"internal": "json"}'),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();
    get($repository->url('/_mirror/acme/package/aaa'))->assertStatus(502);

    expect(MirrorDist::query()->count())->toBe(0);
});

it('only sends credentials over https or to the exact upstream origin', function (): void {
    $upstream = Upstream::factory()
        ->url('https://gitlab.test/packages.json')
        ->withCredential('gitlab.test', UpstreamCredentialType::BEARER, null, 'gitlab-token')
        ->create();
    $repository = mirroredRepository($upstream);
    deployToken(TokenAbility::REPOSITORY_READ, withAccess: true);

    Http::fake([
        'gitlab.test/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'gitlab.test/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [
            upstreamVersion('1.0.0', 'aaa', 'http://gitlab.test:8080/archive.zip'),
        ])),
        'gitlab.test/p2/acme/package~dev.json' => Http::response(null, 404),
        'gitlab.test:8080/*' => Http::response(zipContent()),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();
    get($repository->url('/_mirror/acme/package/aaa'))->assertOk();

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://gitlab.test/')
        && $request->header('Authorization') === ['Bearer gitlab-token']);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'http://gitlab.test:8080/')
        && ! $request->hasHeader('Authorization'));
});

it('sends credentials to plain http upstreams on their own origin', function (): void {
    $upstream = Upstream::factory()
        ->url('http://satis.test:8124/r/private')
        ->withCredential('satis.test', UpstreamCredentialType::BEARER, null, 'satis-token')
        ->create();
    $repository = mirroredRepository($upstream);
    deployToken(TokenAbility::REPOSITORY_READ, withAccess: true);

    Http::fake([
        'satis.test:8124/r/private/packages.json' => Http::response(['metadata-url' => '/r/private/p2/%package%.json']),
        'satis.test:8124/r/private/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')])),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    Http::assertSent(fn (Request $request): bool => $request->header('Authorization') === ['Bearer satis-token']);
});

it('only accepts http and https upstream urls', function (string $url): void {
    user(Permission::UPSTREAM_CREATE);

    postJson('/api/upstreams', [
        'name' => 'Sneaky',
        'url' => $url,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['url']);

    expect(Package::query()->count())->toBe(0);
})->with([
    'gopher' => 'gopher://127.0.0.1:6379/_FLUSHALL',
    'ftp' => 'ftp://files.test/packages.json',
    'file' => 'file:///etc/passwd',
]);
