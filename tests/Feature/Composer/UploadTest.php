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

    $package = $repository->packages->first();

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

    $fileName = $repository->archivePath('test-test-1.0.0.zip');

    Storage::disk()->assertExists($fileName, $file->getContent());

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
        personalTokenWithoutAccessStatus: 401,
        personalTokenWithAccessStatus: 201,
        unscopedPersonalTokenWithoutAccessStatus: 201,
        deployTokenWithoutAccessStatus: 401,
        deployTokenWithAccessStatus: 201,
        deployTokenWithoutPackagesStatus: 401,
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

    $fileName = $repository->archivePath('test-test-1.0.0.zip');

    Storage::disk()->assertExists($fileName, $file->getContent());

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
        personalTokenWithoutAccessStatus: 401,
        personalTokenWithAccessStatus: 201,
        unscopedPersonalTokenWithoutAccessStatus: 201,
        deployTokenWithoutAccessStatus: 401,
        deployTokenWithAccessStatus: 201,
        deployTokenWithoutPackagesStatus: 401,
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
        personalTokenWithoutAccessStatus: 401,
        personalTokenWithAccessStatus: 422,
        unscopedPersonalTokenWithoutAccessStatus: 422,
        deployTokenWithoutAccessStatus: 401,
        deployTokenWithAccessStatus: 422,
        deployTokenWithoutPackagesStatus: 401,
    ));
