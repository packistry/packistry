<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;

it('deletes tag', function (Repository $repository, SourceProvider $provider, ...$args): void {
    $package = Package::factory()
        ->for($repository)
        ->name('vendor/test')
        ->provider($provider)
        ->create();

    $version = Version::factory()
        ->for($package)
        ->name('1.0.0')
        ->create();

    webhook($repository, $provider, ...$args)
        ->assertOk()
        ->assertExactJson([
            'id' => $version->id,
            'package_id' => $version->package->id,
            'name' => $version->name,
            'metadata' => $version->metadata,
            'shasum' => $version->shasum,
            'created_at' => $version->created_at,
            'updated_at' => $version->updated_at,
        ]);

    expect(Version::query()->count())->toBe(0);
})
    ->with(rootAndSubRepository())
    ->with(providerDeleteEvents());
