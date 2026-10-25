<?php

declare(strict_types=1);

use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    Storage::fake();

    $package = Package::factory()
        ->for(Repository::factory())
        ->create(['name' => 'vendor/package']);

    Version::factory()->for($package)->create([
        'name' => '1.0.0',
        'metadata' => ['require' => ['php' => '^8.4']],
        'archive_path' => 'valid.zip',
    ]);

    Version::factory()->for($package)->create([
        'name' => '1.0.1',
        'metadata' => ['require' => ['laravel/framework' => '^^12']],
        'archive_path' => 'invalid.zip',
    ]);

    Storage::put('valid.zip', 'valid');
    Storage::put('invalid.zip', 'invalid');
});

it('removes versions with constraints composer cannot parse', function (): void {
    artisan('versions:clean')
        ->expectsOutputToContain('Removing: vendor/package 1.0.1 (require.laravel/framework)')
        ->expectsOutputToContain('1 version(s) were removed.')
        ->assertSuccessful();

    expect(Version::query()->pluck('name')->all())->toBe(['1.0.0']);

    Storage::assertExists('valid.zip');
    Storage::assertMissing('invalid.zip');
});

it('does not remove anything on dry run', function (): void {
    artisan('versions:clean', ['--dry-run' => true])
        ->expectsOutputToContain('Removing: vendor/package 1.0.1 (require.laravel/framework)')
        ->expectsOutputToContain('1 version(s) would be removed.')
        ->assertSuccessful();

    expect(Version::query()->count())->toBe(2);

    Storage::assertExists('invalid.zip');
});
