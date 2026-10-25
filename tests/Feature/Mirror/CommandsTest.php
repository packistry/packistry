<?php

declare(strict_types=1);

use App\Enums\MirrorStatus;
use App\Enums\Permission;
use App\Models\Download;
use App\Models\MirrorDist;
use App\Models\MirrorMetadata;
use App\Models\Package;
use App\Models\Upstream;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;
use function Pest\Laravel\getJson;
use function Pest\Laravel\travel;

beforeEach(function (): void {
    Storage::fake();
    Http::preventStrayRequests();
});

function fakePackagistWithDist(string $version = '1.0.0'): void
{
    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [upstreamVersion($version, 'aaa')])),
        'repo.packagist.org/p2/acme/package~dev.json' => Http::response(null, 404),
        'api.github.com/*' => Http::response((string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')),
    ]);
}

it('warms the cache from a composer.lock', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream);
    Package::factory()->for($repository)->name('acme/local')->create();

    fakePackagistWithDist();

    $lock = tempnam(sys_get_temp_dir(), 'lock');
    file_put_contents((string) $lock, (string) json_encode([
        'packages' => [
            ['name' => 'acme/package', 'version' => '1.0.0', 'dist' => ['type' => 'zip', 'url' => 'https://api.github.com/repos/acme/package/zipball/aaa', 'reference' => 'aaa']],
            ['name' => 'acme/local', 'version' => '2.0.0', 'dist' => ['type' => 'zip', 'url' => 'https://packistry.test/acme/local/2.0.0']],
        ],
        'packages-dev' => [],
    ]));

    artisan('mirror:warm', ['lock' => $lock, '--repository' => $repository->path])
        ->expectsOutputToContain('local package')
        ->assertSuccessful();

    /** @var MirrorDist $dist */
    $dist = MirrorDist::query()->sole();

    expect($dist->name)->toBe('acme/package')
        ->and(Package::query()->where('name', 'acme/package')->sole()->upstream_id)->toBe($upstream->id);

    Storage::assertExists($dist->archive_path);

    unlink((string) $lock);
});

it('warms dists without a reference from a lock file created through packistry', function (): void {
    $upstream = Upstream::factory()->url('https://packistry-b.test/r/private')->create();
    $repository = mirroredRepository($upstream);

    // packistry itself publishes dists without a reference, so they are keyed by their url
    $version = upstreamVersion('1.0.0', 'unused', 'https://packistry-b.test/r/private/acme/private-lib/1.0.0');
    unset($version['dist']['reference']);

    Http::fake([
        'packistry-b.test/r/private/packages.json' => Http::response(['metadata-url' => '/r/private/p2/%package%.json']),
        'packistry-b.test/r/private/p2/acme/private-lib.json' => Http::response(upstreamMetadata('acme/private-lib', [$version])),
        'packistry-b.test/r/private/p2/acme/private-lib~dev.json' => Http::response(null, 404),
        'packistry-b.test/r/private/acme/private-lib/1.0.0' => Http::response((string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')),
    ]);

    $key = sha1('https://packistry-b.test/r/private/acme/private-lib/1.0.0');

    $lock = tempnam(sys_get_temp_dir(), 'lock');
    file_put_contents((string) $lock, (string) json_encode([
        'packages' => [
            ['name' => 'acme/private-lib', 'version' => '1.0.0', 'dist' => ['type' => 'zip', 'url' => $repository->url("/_mirror/acme/private-lib/$key")]],
        ],
    ]));

    artisan('mirror:warm', ['lock' => $lock, '--repository' => $repository->path])->assertSuccessful();

    expect(MirrorDist::query()->sole()->reference)->toBe($key);

    unlink((string) $lock);
});

it('fails warming packages that are not available upstream', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/*' => Http::response(null, 404),
    ]);

    $lock = tempnam(sys_get_temp_dir(), 'lock');
    file_put_contents((string) $lock, (string) json_encode([
        'packages' => [['name' => 'acme/missing', 'version' => '1.0.0']],
    ]));

    artisan('mirror:warm', ['lock' => $lock, '--repository' => $repository->path])
        ->expectsOutputToContain('not found in any upstream')
        ->assertFailed();

    unlink((string) $lock);
});

it('refreshes cached metadata before it expires', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::sequence()
            ->push(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')]))
            ->push(upstreamMetadata('acme/package', [upstreamVersion('2.0.0', 'bbb'), upstreamVersion('1.0.0', 'aaa')])),
    ]);

    getJson($repository->url('/p2/acme/package.json'))
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    artisan('mirror:refresh', ['package' => 'acme/package'])->assertSuccessful();

    // still within the ttl, served from the refreshed cache
    getJson($repository->url('/p2/acme/package.json'))
        ->assertJsonPath('packages.acme/package.0.version', '2.0.0');

    Http::assertSentCount(3);
});

it('reports metadata that cannot be refreshed', function (): void {
    $upstream = Upstream::factory()->packagist()->create(['metadata_url' => 'https://repo.packagist.org/p2/%package%.json', 'root_checked_at' => now()]);

    MirrorMetadata::query()->forceCreate([
        'upstream_id' => $upstream->id,
        'name' => 'acme/package',
        'dev' => false,
        'body_path' => $upstream->storagePath('metadata/acme/package.json.gz'),
        'checked_at' => now(),
    ]);
    Storage::put($upstream->storagePath('metadata/acme/package.json.gz'), (string) gzencode((string) json_encode(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')]))));

    Http::fake(['*' => Http::failedConnection()]);

    artisan('mirror:refresh', ['--upstream' => 'Packagist'])
        ->expectsOutputToContain('keeping the cached copy')
        ->assertFailed();

    Storage::assertExists($upstream->storagePath('metadata/acme/package.json.gz'));
});

it('prunes unused dists, metadata and orphaned files', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagistWithDist();

    getJson($repository->url('/p2/acme/package.json'))->assertOk();
    Pest\Laravel\get($repository->url('/_mirror/acme/package/aaa'))->assertOk();

    Storage::put('_mirror/999/dists/old.zip', 'deleted upstream');
    Storage::put($upstream->storagePath('dists/stray.zip'), 'no row');

    /** @var MirrorDist $dist */
    $dist = MirrorDist::query()->sole();

    artisan('mirror:prune', ['--unused-for' => 30])->assertSuccessful();

    // recently used files are kept, orphans are removed
    Storage::assertExists($dist->archive_path);
    Storage::assertMissing('_mirror/999/dists/old.zip');
    Storage::assertMissing($upstream->storagePath('dists/stray.zip'));

    travel(31)->days();

    artisan('mirror:prune', ['--unused-for' => 30, '--dry-run' => true])->assertSuccessful();
    expect(MirrorDist::query()->count())->toBe(1);

    artisan('mirror:prune', ['--unused-for' => 30])->assertSuccessful();

    expect(MirrorDist::query()->count())->toBe(0)
        ->and(MirrorMetadata::query()->count())->toBe(0)
        ->and(Storage::allFiles('_mirror'))->toBe([]);
});

it('prunes old download records but keeps the totals', function (): void {
    $package = Package::factory()->for(repository())->create(['total_downloads' => 3]);

    Download::factory()->create(['package_id' => $package->id, 'version_id' => null, 'version_name' => '1.0.0', 'created_at' => now()->subDays(400)]);
    Download::factory()->create(['package_id' => $package->id, 'version_id' => null, 'version_name' => '1.0.0', 'created_at' => now()->subDays(400)]);
    Download::factory()->create(['package_id' => $package->id, 'version_id' => null, 'version_name' => '1.0.0', 'created_at' => now()->subDays(10)]);

    artisan('downloads:prune', ['--older-than' => 30])->assertFailed();
    artisan('downloads:prune', ['--older-than' => 365])->assertSuccessful();

    expect(Download::query()->count())->toBe(1)
        ->and($package->refresh()->total_downloads)->toBe(3);
});

it('answers conditional requests for mirrored metadata', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagistWithDist();

    $response = getJson($repository->url('/p2/acme/package.json'))->assertOk();
    $lastModified = $response->headers->get('Last-Modified');

    expect($lastModified)->not->toBeNull();

    Pest\Laravel\getJson($repository->url('/p2/acme/package.json'), ['If-Modified-Since' => (string) $lastModified])
        ->assertStatus(304)
        ->assertContent('');

    // moving the package to another upstream invalidates what clients have cached
    travel(5)->seconds();
    Package::query()->sole()->touch();

    Pest\Laravel\getJson($repository->url('/p2/acme/package.json'), ['If-Modified-Since' => (string) $lastModified])
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    Http::assertNotSent(fn (Request $request): bool => $request->hasHeader('If-Modified-Since'));
});

it('refreshes a mirrored package from the admin api', function (): void {
    user([Permission::PACKAGE_UPDATE, Permission::UNSCOPED]);

    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream);
    $package = Package::factory()->for($repository)->name('acme/package')->state(['upstream_id' => $upstream->id, 'mirror_status' => MirrorStatus::GONE])->create();

    fakePackagistWithDist('3.0.0');

    Pest\Laravel\postJson("/api/packages/$package->id/refresh")
        ->assertOk()
        ->assertJsonPath('mirror_status', 'ok')
        ->assertJsonPath('latest_version', '3.0.0')
        ->assertJsonPath('upstream.id', $upstream->id);

    $local = Package::factory()->for($repository)->name('acme/local')->create();

    Pest\Laravel\postJson("/api/packages/$local->id/refresh")
        ->assertUnprocessable();
});

it('does not report a refresh as successful when the upstream is down', function (): void {
    user([Permission::PACKAGE_UPDATE, Permission::UNSCOPED]);

    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    fakePackagistWithDist();
    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    Http::fake(['*' => Http::failedConnection()]);

    Pest\Laravel\postJson('/api/packages/'.Package::query()->sole()->id.'/refresh')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['package']);

    // the cached copy is still served
    getJson($repository->url('/p2/acme/package.json'))->assertOk();
});
