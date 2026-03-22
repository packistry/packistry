<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\RepositorySyncMode;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use App\Models\Version;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\post;

$composerJson = json_encode([
    'name' => 'test/test',
    'description' => 'description',
    'version' => '1.0.0',
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
    'require' => new stdClass,
], JSON_THROW_ON_ERROR);

$zipContent = static function (array $entries): string {
    $temporary = tempnam(sys_get_temp_dir(), 'packistry-upload-test-');

    if ($temporary === false) {
        throw new RuntimeException('failed to create temporary file for ZIP fixture');
    }

    $path = $temporary.'.zip';

    if (! rename($temporary, $path)) {
        @unlink($temporary);

        throw new RuntimeException('failed to prepare temporary ZIP path');
    }

    $zip = new ZipArchive;

    try {
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('failed to open temporary ZIP archive');
        }

        foreach ($entries as $name => $content) {
            if (str_ends_with($name, '/')) {
                $zip->addEmptyDir(rtrim($name, '/'));

                continue;
            }

            $zip->addFromString($name, $content);
        }

        $zip->close();

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException('failed to read temporary ZIP archive');
        }

        return $content;
    } finally {
        @unlink($path);
    }
};

it('uploads zip for repository and creates package', function (?User $user, int $status): void {
    Storage::fake();

    $repository = Repository::factory()->state([
        'sync_mode' => RepositorySyncMode::MANUAL,
        'name' => 'test/test',
    ])->create();

    $file = UploadedFile::fake()->createWithContent(
        name: 'project.zip',
        content: (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')
    );

    $response = post(
        "/api/repositories/$repository->id/uploads",
        [
            'file' => $file,
        ],
        [
            'Accept' => 'application/json',
        ],
    )->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    /** @var Package $package */
    $package = Package::query()->firstOrFail();
    /** @var Version $version */
    $version = Version::query()->firstOrFail();

    $response->assertJsonPath('package_id', $package->id);
    $response->assertJsonPath('name', '1.0.0');

    expect($package->name)->toBe('test/test');
    Storage::disk()->assertExists($version->archive_path, $file->getContent());
})->with(guestAndUsers(Permission::PACKAGE_CREATE, userWithPermission: 201));

it('validates upload payload', function (): void {
    user([Permission::UNSCOPED, Permission::PACKAGE_CREATE]);

    $repository = Repository::factory()->create();

    post(
        "/api/repositories/$repository->id/uploads",
        [
            'file' => UploadedFile::fake()->create('archive.txt', 10, 'text/plain'),
        ],
        [
            'Accept' => 'application/json',
        ],
    )
        ->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

it('allows upload for source sync repository', function (): void {
    user([Permission::UNSCOPED, Permission::PACKAGE_CREATE]);

    Storage::fake();

    $repository = Repository::factory()->create();

    $file = UploadedFile::fake()->createWithContent(
        name: 'project.zip',
        content: (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')
    );

    post(
        "/api/repositories/$repository->id/uploads",
        [
            'file' => $file,
        ],
        [
            'Accept' => 'application/json',
        ],
    )
        ->assertStatus(201);
});

it('uploads zip when composer.json is in root but not first entry', function () use ($composerJson, $zipContent): void {
    user([Permission::UNSCOPED, Permission::PACKAGE_CREATE]);

    $repository = Repository::factory()->state([
        'sync_mode' => RepositorySyncMode::MANUAL,
        'name' => 'test/test',
    ])->create();

    $file = UploadedFile::fake()->createWithContent(
        name: 'project.zip',
        content: $zipContent([
            'src/' => '',
            'composer.json' => $composerJson,
        ])
    );

    post(
        "/api/repositories/$repository->id/uploads",
        ['file' => $file],
        ['Accept' => 'application/json'],
    )->assertStatus(201);
});

it('uploads nested zip when composer.json is in top-level directory', function () use ($composerJson, $zipContent): void {
    user([Permission::UNSCOPED, Permission::PACKAGE_CREATE]);

    $repository = Repository::factory()->state([
        'sync_mode' => RepositorySyncMode::MANUAL,
        'name' => 'test/test',
    ])->create();

    $file = UploadedFile::fake()->createWithContent(
        name: 'project.zip',
        content: $zipContent([
            'project/' => '',
            'project/src/' => '',
            'project/composer.json' => $composerJson,
        ])
    );

    post(
        "/api/repositories/$repository->id/uploads",
        ['file' => $file],
        ['Accept' => 'application/json'],
    )->assertStatus(201);
});

it('rejects zip when composer.json is only nested deeper than top-level directory', function () use ($composerJson, $zipContent): void {
    user([Permission::UNSCOPED, Permission::PACKAGE_CREATE]);

    $repository = Repository::factory()->state([
        'sync_mode' => RepositorySyncMode::MANUAL,
    ])->create();

    $file = UploadedFile::fake()->createWithContent(
        name: 'project.zip',
        content: $zipContent([
            'project/' => '',
            'project/src/' => '',
            'project/src/composer.json' => $composerJson,
        ])
    );

    post(
        "/api/repositories/$repository->id/uploads",
        ['file' => $file],
        ['Accept' => 'application/json'],
    )
        ->assertStatus(422)
        ->assertExactJson(validation([
            'file' => ['composer.json not found in archive'],
        ]));
});

it('rejects zip when archive has multiple top-level directories without root composer.json', function () use ($composerJson, $zipContent): void {
    user([Permission::UNSCOPED, Permission::PACKAGE_CREATE]);

    $repository = Repository::factory()->state([
        'sync_mode' => RepositorySyncMode::MANUAL,
    ])->create();

    $file = UploadedFile::fake()->createWithContent(
        name: 'project.zip',
        content: $zipContent([
            'project/' => '',
            'project/composer.json' => $composerJson,
            'docs/' => '',
            'docs/readme.md' => '# docs',
        ])
    );

    post(
        "/api/repositories/$repository->id/uploads",
        ['file' => $file],
        ['Accept' => 'application/json'],
    )
        ->assertStatus(422)
        ->assertExactJson(validation([
            'file' => ['composer.json not found in archive'],
        ]));
});
