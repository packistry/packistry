<?php

declare(strict_types=1);

use App\Enums\MirrorStatus;
use App\Enums\Permission;
use App\Enums\SourceProvider;
use App\Enums\TokenAbility;
use App\Import;
use App\Models\Download;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Source;
use App\Models\Upstream;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    Storage::fake();
    Http::preventStrayRequests();
});

function mirroredPackage(Repository $repository, Upstream $upstream, string $name, MirrorStatus $status = MirrorStatus::OK): Package
{
    return Package::factory()
        ->for($repository)
        ->name($name)
        ->state(['upstream_id' => $upstream->id, 'mirror_status' => $status, 'provider_id' => null])
        ->create();
}

it('hides mirrored packages from the package index by default', function (): void {
    user([Permission::PACKAGE_READ, Permission::UNSCOPED]);

    $upstream = Upstream::factory()->create();
    $repository = mirroredRepository($upstream);

    Package::factory()->for($repository)->name('acme/local')->create();
    mirroredPackage($repository, $upstream, 'acme/mirrored');

    $names = fn (string $query): array => collect(getJson("/api/packages$query")->assertOk()->json('data'))->pluck('name')->sort()->values()->all();

    expect($names(''))->toBe(['acme/local'])
        ->and($names('?filter[origin]=local'))->toBe(['acme/local'])
        ->and($names('?filter[origin]=mirrored'))->toBe(['acme/mirrored'])
        ->and($names('?filter[origin]=all'))->toBe(['acme/local', 'acme/mirrored'])
        ->and($names("?filter[upstream_id]=$upstream->id&filter[origin]=all"))->toBe(['acme/mirrored']);
});

it('shows the upstream of a mirrored package', function (): void {
    user([Permission::PACKAGE_READ, Permission::UNSCOPED]);

    $upstream = Upstream::factory()->create(['name' => 'Packagist']);
    $package = mirroredPackage(mirroredRepository($upstream), $upstream, 'acme/mirrored');

    getJson("/api/packages/$package->id")
        ->assertOk()
        ->assertJsonPath('mirror_status', 'ok')
        ->assertJsonPath('upstream', ['id' => $upstream->id, 'name' => 'Packagist']);
});

it('lists versions of a mirrored package from the upstream metadata', function (): void {
    user([Permission::PACKAGE_READ, Permission::UNSCOPED]);

    $upstream = Upstream::factory()->packagist()->create();
    $package = mirroredPackage(mirroredRepository($upstream), $upstream, 'acme/package');

    Download::factory()->count(3)->create(['package_id' => $package->id, 'version_id' => null, 'version_name' => '1.0.0']);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::response(upstreamMetadata('acme/package', [
            upstreamVersion('2.0.0', 'bbb'),
            upstreamVersion('1.0.0', 'aaa'),
        ])),
        'repo.packagist.org/p2/acme/package~dev.json' => Http::response(upstreamMetadata('acme/package', [
            upstreamVersion('dev-main', 'ccc'),
        ])),
    ]);

    getJson("/api/packages/$package->id/versions")
        ->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('data.0.name', '2.0.0')
        ->assertJsonPath('data.1.name', '1.0.0')
        ->assertJsonPath('data.1.total_downloads', 3)
        ->assertJsonPath('data.2.name', 'dev-main');

    getJson("/api/packages/$package->id/versions?sort=-total_downloads&filter[search]=1.")
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.name', '1.0.0');
});

it('counts local and mirrored packages separately on the dashboard', function (): void {
    user([Permission::DASHBOARD, Permission::PACKAGE_READ, Permission::UNSCOPED]);

    $upstream = Upstream::factory()->create();
    $repository = mirroredRepository($upstream);

    Package::factory()->for($repository)->create();
    mirroredPackage($repository, $upstream, 'acme/one');
    mirroredPackage($repository, $upstream, 'acme/two');
    mirroredPackage($repository, $upstream, 'acme/gone', MirrorStatus::GONE);

    getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonPath('packages', 1)
        ->assertJsonPath('mirrored_packages', 2);
});

it('converts a mirrored package to local when a version is uploaded', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);
    $package = mirroredPackage($repository, $upstream, 'test/test');
    $package->total_downloads = 5;
    $package->save();

    Download::factory()->create(['package_id' => $package->id, 'version_id' => null, 'version_name' => '0.9.0']);

    deployToken(TokenAbility::REPOSITORY_WRITE, withAccess: true);

    post($repository->url('/test/test'), [
        'file' => UploadedFile::fake()->createWithContent('project.zip', (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')),
    ])->assertCreated();

    expect($package->refresh())
        ->upstream_id->toBeNull()
        ->mirror_status->toBeNull()
        ->total_downloads->toBe(5)
        ->and(Download::query()->where('package_id', $package->id)->count())->toBe(1);

    getJson($repository->url('/p2/test/test.json'))
        ->assertOk()
        ->assertJsonPath('packages.test/test.0.version', '1.0.0');

    Http::assertNothingSent();
});

it('converts a mirrored package to local when it is imported from a source', function (): void {
    user([Permission::PACKAGE_CREATE, Permission::UNSCOPED]);

    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream);
    $package = mirroredPackage($repository, $upstream, 'test/test');

    $source = Source::factory()->provider(SourceProvider::GITLAB)->create();

    Http::fake([
        'https://fake.com/archive.zip' => Http::response(
            (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip'),
            headers: ['content-type' => 'application/zip']
        ),
    ]);

    $clientClassString = SourceProvider::GITLAB->clientClassString();

    app()->singleton($clientClassString, function () use ($clientClassString) {
        /** @var Client $client */
        $client = new $clientClassString(app(Import::class));
        $client->withOptions('token', 'https://url.url');

        $mock = Mockery::mock($client)->shouldIgnoreMissing(false);
        $mock->shouldReceive('withOptions')->withAnyArgs()->andReturn($mock);
        $mock->shouldReceive('project')->withAnyArgs()->andReturn(new Project(
            id: 1,
            fullName: 'test/test',
            name: 'test',
            url: 'https://gitlab.com/test/test',
            webUrl: 'https://gitlab.com/test/test',
        ));
        $mock->shouldReceive('branches')->withAnyArgs()->andReturn(LazyCollection::wrap([]));
        $mock->shouldReceive('tags')->withAnyArgs()->andReturn(LazyCollection::wrap([
            new Tag(
                id: '1',
                name: '1.0.0',
                url: 'https://gitlab.com/test/test',
                zipUrl: 'https://fake.com/archive.zip',
                sourceUrl: 'https://gitlab.com/test/test',
            ),
        ]));

        return $mock;
    });

    postJson('/api/packages', [
        'repository' => (string) $repository->id,
        'source' => (string) $source->id,
        'projects' => ['1'],
        'webhook' => false,
    ])->assertCreated();

    expect(Package::query()->count())->toBe(1)
        ->and($package->refresh())
        ->upstream_id->toBeNull()
        ->mirror_status->toBeNull()
        ->source_id->toBe($source->id)
        ->provider_id->toBe('1')
        ->and($package->versions()->pluck('name')->all())->toBe(['1.0.0']);
});

it('keeps serving a converted package as mirrored in other repositories', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $converted = mirroredRepository($upstream, public: true, path: 'converted');
    $other = mirroredRepository($upstream, public: true, path: 'other');

    mirroredPackage($converted, $upstream, 'test/test');
    $mirrored = mirroredPackage($other, $upstream, 'test/test');

    deployToken(TokenAbility::REPOSITORY_WRITE, withAccess: true);

    post($converted->url('/test/test'), [
        'file' => UploadedFile::fake()->createWithContent('project.zip', (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')),
    ])->assertCreated();

    expect($mirrored->refresh())
        ->upstream_id->toBe($upstream->id)
        ->mirror_status->toBe(MirrorStatus::OK);
});

it('does not clean mirror files when cleaning archives', function (): void {
    $upstream = Upstream::factory()->create();
    Repository::factory()->root()->create();

    Storage::put($path = $upstream->storagePath('dists/acme/package/aaa.zip'), 'zip');
    Storage::put('orphan.zip', 'zip');

    Artisan::call('archives:clean');

    Storage::assertExists($path);
    Storage::assertMissing('orphan.zip');
});

it('does not serve local archives through the mirror route', function (): void {
    $repository = repository(public: true);
    Package::factory()->for($repository)->name('acme/local')->create();

    get($repository->url('/_mirror/acme/local/abc'))->assertNotFound();
});

it('only counts local packages of repositories', function (): void {
    user([Permission::REPOSITORY_READ, Permission::UNSCOPED]);

    $upstream = Upstream::factory()->create();
    $repository = mirroredRepository($upstream);

    Package::factory()->for($repository)->name('acme/local')->create();
    mirroredPackage($repository, $upstream, 'acme/mirrored');

    getJson('/api/repositories')
        ->assertOk()
        ->assertJsonPath('data.0.packages_count', 1);
});
