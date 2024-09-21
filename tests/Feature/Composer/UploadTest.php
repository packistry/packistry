<?php

declare(strict_types=1);

use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\post;
use function PHPUnit\Framework\assertNotNull;

it('uploads new version', function (): void {
    Storage::fake();

    $file = UploadedFile::fake()
        ->createWithContent(
            name: 'project.zip',
            content: (string) file_get_contents(__DIR__.'/../../Fixtures/project.zip')
        );

    /** @var Repository $repository */
    $repository = Repository::factory()
        ->root()
        ->has(
            Package::factory()
                ->state([
                    'name' => 'test/test',
                ])
        )
        ->create();

    $package = $repository->packages->first();

    assertNotNull($package);

    post("/$package->name", [
        'file' => $file,
    ])
        ->assertCreated();

    $fileName = 'test-test-1.0.0.zip';

    Storage::disk()->assertExists($fileName, $file->getContent());

    /** @var Version $version */
    $version = Version::query()->first();



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
});
