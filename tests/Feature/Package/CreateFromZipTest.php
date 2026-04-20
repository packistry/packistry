<?php

declare(strict_types=1);

use App\CreateFromZip;
use App\Models\Package;
use App\Models\Repository;
use Illuminate\Support\Facades\Storage;

/**
 * Creates a temporary zip file containing a composer.json with the given fields.
 *
 * @param  array<string, mixed>  $extra
 */
function makeComposerZip(string $name, string $version, array $extra = []): string
{
    $path = tempnam(sys_get_temp_dir(), 'packistry_test_').'.zip';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('composer.json', json_encode(array_merge([
        'name' => $name,
        'version' => $version,
    ], $extra)));
    $zip->close();

    return $path;
}

it('does not overwrite the package name with an older version imported after a newer one', function (): void {
    Storage::fake();

    $repository = Repository::factory()->create();
    /** @var Package $package */
    $package = Package::factory()->for($repository)->create(['name' => 'vendor/initial']);

    $createFromZip = app(CreateFromZip::class);

    $newZip = makeComposerZip('vendor/new-name', '2.0.0', ['replace' => ['vendor/old-name' => '*']]);
    $oldZip = makeComposerZip('vendor/old-name', '1.0.0');

    try {
        // Import the newer version first — sets the correct name
        $createFromZip->create($package, $newZip, '2.0.0');
        $package->refresh();
        expect($package->name)->toBe('vendor/new-name');

        // Import the older version second — must NOT overwrite the name
        $createFromZip->create($package, $oldZip, '1.0.0');
        $package->refresh();
        expect($package->name)->toBe('vendor/new-name');
    } finally {
        @unlink($newZip);
        @unlink($oldZip);
    }
});

it('updates the package name when a newer version is imported after an older one', function (): void {
    Storage::fake();

    $repository = Repository::factory()->create();
    /** @var Package $package */
    $package = Package::factory()->for($repository)->create(['name' => 'vendor/initial']);

    $createFromZip = app(CreateFromZip::class);

    $oldZip = makeComposerZip('vendor/old-name', '1.0.0');
    $newZip = makeComposerZip('vendor/new-name', '2.0.0', ['replace' => ['vendor/old-name' => '*']]);

    try {
        // Import the older version first
        $createFromZip->create($package, $oldZip, '1.0.0');
        $package->refresh();
        expect($package->name)->toBe('vendor/old-name');

        // Import the newer version second — must update to the new name
        $createFromZip->create($package, $newZip, '2.0.0');
        $package->refresh();
        expect($package->name)->toBe('vendor/new-name');
    } finally {
        @unlink($oldZip);
        @unlink($newZip);
    }
});
