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
        personalTokenWithoutAccessStatus: 404,
        personalTokenWithAccessStatus: 201,
        unscopedPersonalTokenWithoutAccessStatus: 201,
        deployTokenWithoutAccessStatus: 404,
        deployTokenWithAccessStatus: 201,
        deployTokenWithoutPackagesStatus: 404,
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
        personalTokenWithoutAccessStatus: 404,
        personalTokenWithAccessStatus: 201,
        unscopedPersonalTokenWithoutAccessStatus: 201,
        deployTokenWithoutAccessStatus: 404,
        deployTokenWithAccessStatus: 201,
        deployTokenWithoutPackagesStatus: 404,
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
        personalTokenWithoutAccessStatus: 404,
        personalTokenWithAccessStatus: 422,
        unscopedPersonalTokenWithoutAccessStatus: 422,
        deployTokenWithoutAccessStatus: 404,
        deployTokenWithAccessStatus: 422,
        deployTokenWithoutPackagesStatus: 404,
    ));

it('rejects version with constraints composer cannot parse', function (): void {
    Storage::fake();

    $repository = repository(public: true, closure: fn (RepositoryFactory $factory) => $factory->has(
        Package::factory()->state(['name' => 'test/test'])
    ));

    /** @var Package $package */
    $package = $repository->packages()->firstOrFail();
    deployTokenWithPackageAccess($package, TokenAbility::REPOSITORY_WRITE);

    $path = tempnam(sys_get_temp_dir(), 'packistry_test_');

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('composer.json', (string) json_encode([
        'name' => 'test/test',
        'version' => '1.0.0',
        'require' => [
            'laravel/framework' => '^^12',
        ],
    ]));
    $zip->close();

    $file = UploadedFile::fake()
        ->createWithContent(
            name: 'project.zip',
            content: (string) file_get_contents($path)
        );

    @unlink($path);

    post($repository->url('/test/test'), ['file' => $file])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'file' => 'invalid version constraint in require.laravel/framework',
        ]);

    expect(Version::query()->count())->toBe(0);
});

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
        ->assertNotFound();

    expect(Version::query()->count())->toBe(0);
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
        ->assertNotFound();

    expect(Version::query()->count())->toBe(0);
});

it('does not let a package grant in a repository write to its other packages', function (): void {
    Storage::fake();

    $repository = repository(closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()->state(['name' => 'allowed/allowed']))
        ->has(Package::factory()->state(['name' => 'test/test']))
    );

    /** @var Package $allowedPackage */
    $allowedPackage = $repository->packages()->where('name', 'allowed/allowed')->firstOrFail();
    personalToken(TokenAbility::REPOSITORY_WRITE)->packages()->sync([$allowedPackage->id]);

    $file = UploadedFile::fake()
        ->createWithContent(
            name: 'project.zip',
            content: (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')
        );

    post($repository->url('/test/test'), ['file' => $file])
        ->assertNotFound();

    expect(Version::query()->count())->toBe(0);
});

it('rejects uploads whose composer.json names another package', function (): void {
    Storage::fake();

    $repository = repository(public: true, closure: fn (RepositoryFactory $factory) => $factory->has(
        Package::factory()->state(['name' => 'acme/package'])
    ));

    deployToken(TokenAbility::REPOSITORY_WRITE, withAccess: true);

    // the fixture zip is named test/test
    $file = fn (): UploadedFile => UploadedFile::fake()
        ->createWithContent(
            name: 'project.zip',
            content: (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')
        );

    post($repository->url('/acme/package'), ['file' => $file()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);

    post($repository->url('/acme/new-package'), ['file' => $file()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);

    expect(Package::query()->pluck('name')->all())->toBe(['acme/package'])
        ->and(Version::query()->count())->toBe(0);
});
