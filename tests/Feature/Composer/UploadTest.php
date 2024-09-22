<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Database\Factories\RepositoryFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\post;
use function PHPUnit\Framework\assertNotNull;

it('creates new version for existing package', function (Repository $repository): void {
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

    user(Ability::REPOSITORY_WRITE);

    $response = post($repository->url("/$package->name"), $attributes)
        ->assertCreated();

    /** @var Version $version */
    $version = Version::query()->first();

    $response->assertJsonContent([
        'package_id' => $package->id,
        'name' => $version->name,
        'shasum' => $version->shasum,
        'metadata' => $version->metadata,
        'updated_at' => $version->updated_at,
        'created_at' => $version->created_at,
        'id' => $version->id,
    ]);

    $fileName = 'test-test-1.0.0.zip';

    Storage::disk()->assertExists($fileName, $file->getContent());

    /**
     * @phpstan-ignore-next-line
     */
    expect($version)
        ->package_id->toBe($package->id)
        ->name->toBe('1.0.0')
        ->shasum->toBe(hash('sha1', $file->getContent()))
        ->metadata->toBe([
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
})->with(rootAndSubRepository(
    public: true,
    closure: fn (RepositoryFactory $factory) => $factory->has(
        Package::factory()
            ->state([
                'name' => 'test/test',
            ])
    )
));

it('creates new package and version when non exists', function (Repository $repository): void {
    Storage::fake();

    $file = UploadedFile::fake()
        ->createWithContent(
            name: 'project.zip',
            content: (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')
        );

    user(Ability::REPOSITORY_WRITE);

    $response = post($repository->url('/test/test'), [
        'file' => $file,
    ])
        ->assertCreated();

    /** @var Package $package */
    $package = Package::query()->first();

    /** @var Version $version */
    $version = Version::query()->first();

    $response->assertJsonContent([
        'package_id' => $package->id,
        'name' => $version->name,
        'shasum' => $version->shasum,
        'metadata' => $version->metadata,
        'updated_at' => $version->updated_at,
        'created_at' => $version->created_at,
        'id' => $version->id,
    ]);

    $fileName = 'test-test-1.0.0.zip';

    Storage::disk()->assertExists($fileName, $file->getContent());

    /**
     * @phpstan-ignore-next-line
     */
    expect($version)
        ->package_id->toBe($package->id)
        ->name->toBe('1.0.0')
        ->shasum->toBe(hash('sha1', $file->getContent()))
        ->metadata->toBe([
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
})->with(rootAndSubRepository(
    public: true,
));

it('requires authentication', function (Repository $repository): void {
    post($repository->url('/test/test'))
        ->assertUnauthorized();
})->with(rootAndSubRepository(
    public: true,
));

it('requires ability', function (Repository $repository): void {
    user(Ability::REPOSITORY_WRITE);

    post($repository->url('/test/test'))
        ->assertUnprocessable();
})->with(rootAndSubRepository(
    public: true,
));
