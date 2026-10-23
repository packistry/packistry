<?php

declare(strict_types=1);

use App\Enums\MirrorStatus;
use App\Enums\TokenAbility;
use App\Enums\UpstreamCredentialType;
use App\Models\Download;
use App\Models\MirrorDist;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Upstream;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Once;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

beforeEach(function (): void {
    Storage::fake();
    Http::preventStrayRequests();
});

function zipFixture(): string
{
    return (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip');
}

/**
 * Fake packagist serving acme/package 1.0.0 with the given dist response.
 */
function fakePackagist(mixed $dist = null, string $shasum = ''): void
{
    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [
            upstreamVersion('1.0.0', 'aaa', shasum: $shasum),
        ])),
        'repo.packagist.org/p2/acme/package~dev.json' => Http::response(null, 404),
        'api.github.com/repos/acme/package/zipball/aaa' => $dist ?? Http::response(zipFixture(), headers: ['Content-Type' => 'application/zip']),
    ]);
}

function resolveAcmePackage(Repository $repository): void
{
    getJson($repository->url('/p2/acme/package.json'))->assertOk();
}

it('downloads a dist once and serves it from storage afterwards', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagist();
    resolveAcmePackage($repository);

    $response = get($repository->url('/_mirror/acme/package/aaa'))->assertOk();
    expect($response->streamedContent())->toBe(zipFixture());

    $response = get($repository->url('/_mirror/acme/package/aaa'))->assertOk();
    expect($response->streamedContent())->toBe(zipFixture());

    Http::assertSentCount(3);

    /** @var MirrorDist $dist */
    $dist = MirrorDist::query()->sole();

    expect($dist)
        ->upstream_id->toBe($upstream->id)
        ->name->toBe('acme/package')
        ->reference->toBe('aaa')
        ->version->toBe('1.0.0')
        ->shasum->toBe(sha1(zipFixture()));

    Storage::assertExists($dist->archive_path);

    assertDatabaseHas(Download::class, [
        'package_id' => 1,
        'version_id' => null,
        'version_name' => '1.0.0',
    ]);

    expect(Package::query()->sole()->total_downloads)->toBe(2);
});

it('shares downloaded dists between repositories', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $first = mirroredRepository($upstream, public: true, path: 'first');
    $second = mirroredRepository($upstream, public: true, path: 'second');

    fakePackagist();

    resolveAcmePackage($first);
    get($first->url('/_mirror/acme/package/aaa'))->assertOk();

    // the controller (and its memoized repository) is reused between requests within a test
    Once::flush();

    resolveAcmePackage($second);
    get($second->url('/_mirror/acme/package/aaa'))->assertOk();

    // one metadata discovery, one p2 fetch and one dist download in total
    Http::assertSentCount(3);
    expect(MirrorDist::query()->count())->toBe(1)
        ->and(Package::query()->count())->toBe(2);
});

it('rejects dists that do not match the shasum', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagist(shasum: sha1('something else'));
    resolveAcmePackage($repository);

    get($repository->url('/_mirror/acme/package/aaa'))->assertStatus(502);

    expect(MirrorDist::query()->count())->toBe(0)
        ->and(Storage::allFiles($upstream->storagePath('dists')))->toBe([]);
});

it('rejects dists that are not a zip archive', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagist(Http::response('<html>rate limited</html>'));
    resolveAcmePackage($repository);

    get($repository->url('/_mirror/acme/package/aaa'))->assertStatus(502);

    expect(MirrorDist::query()->count())->toBe(0);
});

it('returns 502 when the upstream dist cannot be downloaded', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagist(Http::response('nope', 500));
    resolveAcmePackage($repository);

    get($repository->url('/_mirror/acme/package/aaa'))->assertStatus(502);
});

it('returns 404 for unknown references and packages', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagist();
    resolveAcmePackage($repository);

    get($repository->url('/_mirror/acme/package/unknown'))->assertNotFound();
    get($repository->url('/_mirror/acme/other/aaa'))->assertNotFound();
});

it('does not serve dists of packages that are gone', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagist();
    resolveAcmePackage($repository);

    Package::query()->update(['mirror_status' => MirrorStatus::GONE->value]);

    get($repository->url('/_mirror/acme/package/aaa'))->assertNotFound();
});

it('downloads dists with the credential for the dist host', function (): void {
    $upstream = Upstream::factory()
        ->packagist()
        ->withCredential('api.github.com', UpstreamCredentialType::BEARER, null, 'github-token')
        ->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagist();
    resolveAcmePackage($repository);

    get($repository->url('/_mirror/acme/package/aaa'))->assertOk();

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.github.com')
        && $request->header('Authorization') === ['Bearer github-token']);

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'packagist.org')
        && $request->hasHeader('Authorization'));
});

it('serves stored dists when the upstream is down', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagist();
    resolveAcmePackage($repository);
    get($repository->url('/_mirror/acme/package/aaa'))->assertOk();

    Http::fake(['*' => Http::failedConnection()]);
    Pest\Laravel\travel(301)->seconds();

    getJson($repository->url('/p2/acme/package.json'))->assertOk();
    get($repository->url('/_mirror/acme/package/aaa'))->assertOk();
});

it('respects the download stats setting', function (string $setting, int $downloads, int $total): void {
    config()->set('mirror.download_stats', $setting);

    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagist();
    resolveAcmePackage($repository);

    get($repository->url('/_mirror/acme/package/aaa'))->assertOk();

    expect(Download::query()->count())->toBe($downloads)
        ->and(Package::query()->sole()->total_downloads)->toBe($total);
})->with([
    'full' => ['full', 1, 1],
    'counts' => ['counts', 0, 1],
    'off' => ['off', 0, 0],
]);

it('requires access to the repository', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream);

    fakePackagist();

    getJson($repository->url('/p2/acme/package.json'))->assertNotFound();
    get($repository->url('/_mirror/acme/package/aaa'))->assertNotFound();

    deployToken(TokenAbility::REPOSITORY_READ, withAccess: true);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();
    get($repository->url('/_mirror/acme/package/aaa'))->assertOk();
});
