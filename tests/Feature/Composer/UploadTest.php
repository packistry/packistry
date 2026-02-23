<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Http\Resources\VersionResource;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Database\Factories\RepositoryFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\post;
use function PHPUnit\Framework\assertNotNull;

it('creates new version for existing package', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    Storage::fake();

    $file = UploadedFile::fake()
        ->createWithContent(
            name: 'project.zip',
            content: (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')
        );

    $package = $repository->packages()
        ->where('name', 'test/test')
        ->first();

    assertNotNull($package);

    $attributes = [
        'file' => $file,
    ];

    $response = post($repository->url("/$package->name"), $attributes)
        ->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    /** @var Version $version */
    $version = Version::query()->first();

    $response->assertExactJson(resourceAsJson(new VersionResource($version)));
    Storage::disk()->assertExists($version->archive_path, $file->getContent());

    /**
     * @phpstan-ignore-next-line
     */
    expect($version)
        ->package_id->toBe($package->id)
        ->name->toBe('1.0.0')
        ->shasum->toBe(hash('sha1', $file->getContent()))
        ->metadata->toBe([
            'description' => 'description',
            'autoload' => [
                'psr-4' => [
                    'Test\\Test\\' => 'src/',
                ],
            ],
            'authors' => [
                [
                    'name' => 'Test Test',
                    'email' => 'test@test.test',
                ],
            ],
            'require' => [],
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory->has(
            Package::factory()
                ->state([
                    'name' => 'test/test',
                ])
        )
    ))
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_WRITE,
        guestStatus: 401,
        personalTokenWithoutAccessStatus: 201,
        personalTokenWithAccessStatus: 201,
        unscopedPersonalTokenWithoutAccessStatus: 201,
        deployTokenWithoutAccessStatus: 201,
        deployTokenWithAccessStatus: 201,
        deployTokenWithoutPackagesStatus: 201,
    ));

it('creates new package and version when non existing', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    Storage::fake();

    $file = UploadedFile::fake()
        ->createWithContent(
            name: 'project.zip',
            content: (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')
        );

    $response = post($repository->url('/test/test'), [
        'file' => $file,
    ])
        ->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    /** @var Package $package */
    $package = Package::query()->first();

    /** @var Version $version */
    $version = Version::query()->first();

    $response->assertExactJson(resourceAsJson(new VersionResource($version)));
    Storage::disk()->assertExists($version->archive_path, $file->getContent());

    /**
     * @phpstan-ignore-next-line
     */
    expect($version)
        ->package_id->toBe($package->id)
        ->name->toBe('1.0.0')
        ->shasum->toBe(hash('sha1', $file->getContent()))
        ->metadata->toBe([
            'description' => 'description',
            'autoload' => [
                'psr-4' => [
                    'Test\\Test\\' => 'src/',
                ],
            ],
            'authors' => [
                [
                    'name' => 'Test Test',
                    'email' => 'test@test.test',
                ],
            ],
            'require' => [],
        ])
        ->and($package->name)->toBe('test/test')
        ->and($package->description)->toBe('description');
})
    ->with(rootAndSubRepository(
        public: true,
    ))
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_WRITE,
        guestStatus: 401,
        personalTokenWithoutAccessStatus: 201,
        personalTokenWithAccessStatus: 201,
        unscopedPersonalTokenWithoutAccessStatus: 201,
        deployTokenWithoutAccessStatus: 201,
        deployTokenWithAccessStatus: 201,
        deployTokenWithoutPackagesStatus: 201,
    ));

it('creates package in private repository', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    post($repository->url('/test/test'))
        ->assertStatus($status);
})
    ->with(rootAndSubRepository(
        public: true,
    ))
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_WRITE,
        guestStatus: 401,
        personalTokenWithoutAccessStatus: 422,
        personalTokenWithAccessStatus: 422,
        unscopedPersonalTokenWithoutAccessStatus: 422,
        deployTokenWithoutAccessStatus: 422,
        deployTokenWithAccessStatus: 422,
        deployTokenWithoutPackagesStatus: 422,
    ));

it('allows package-scoped deploy token to upload for allowed package', function (): void {
    Storage::fake();

    $repository = repository(public: true, closure: fn (RepositoryFactory $factory) => $factory->has(
        Package::factory()->state(['name' => 'test/test'])
    ));

    /** @var Package $package */
    $package = $repository->packages()->firstOrFail();
    deployTokenWithPackageAccess($package, TokenAbility::REPOSITORY_WRITE);

    $file = UploadedFile::fake()
        ->createWithContent(
            name: 'project.zip',
            content: (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')
        );

    post($repository->url('/test/test'), ['file' => $file])
        ->assertStatus(201);
});

it('denies package-scoped deploy token upload for package without access', function (): void {
    Storage::fake();

    $repository = repository(public: true, closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()->state(['name' => 'allowed/allowed']))
        ->has(Package::factory()->state(['name' => 'test/test']))
    );

    /** @var Package $allowedPackage */
    $allowedPackage = $repository->packages()->where('name', 'allowed/allowed')->firstOrFail();
    deployTokenWithPackageAccess($allowedPackage, TokenAbility::REPOSITORY_WRITE);

    $file = UploadedFile::fake()
        ->createWithContent(
            name: 'project.zip',
            content: (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')
        );

    post($repository->url('/test/test'), ['file' => $file])
        ->assertStatus(201);
});

it('denies package-scoped deploy token creating a new package', function (): void {
    Storage::fake();

    $repository = repository(public: true, closure: fn (RepositoryFactory $factory) => $factory->has(
        Package::factory()->state(['name' => 'allowed/allowed'])
    ));

    /** @var Package $allowedPackage */
    $allowedPackage = $repository->packages()->where('name', 'allowed/allowed')->firstOrFail();
    deployTokenWithPackageAccess($allowedPackage, TokenAbility::REPOSITORY_WRITE);

    $file = UploadedFile::fake()
        ->createWithContent(
            name: 'project.zip',
            content: (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')
        );

    post($repository->url('/test/test'), ['file' => $file])
        ->assertStatus(201);
});
