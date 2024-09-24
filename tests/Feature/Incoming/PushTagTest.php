<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;

it('creates version for new tag', function (Repository $repository, SourceProvider $provider, ...$args): void {
    Package::factory()
        ->for($repository)
        ->name('vendor/test')
        ->provider($provider)
        ->create();

    $response = webhook($repository, $provider, ...$args)
        ->assertOk();

    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    $response->assertExactJson([
        'package_id' => $version->package->id,
        'name' => '1.0.0',
        'shasum' => $version->shasum,
        'metadata' => $version->metadata,
        'updated_at' => $version->updated_at,
        'created_at' => $version->created_at,
        'id' => $version->id,
    ]);
})
    ->with(rootAndSubRepository())
    ->with(providerPushEvents(
        ref: 'v1.0.0'
    ));

it('overwrites version for same tag', function (Repository $repository, SourceProvider $provider, ...$args): void {
    $package = Package::factory()
        ->name('vendor/test')
        ->for($repository)
        ->provider($provider)
        ->create();

    $originalVersion = Version::factory()
        ->for($package)
        ->fromDefaultZip(
            version: '1.0.0'
        )
        ->create();

    $response = webhook($repository, $provider, ...$args)
        ->assertOk();

    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    $response->assertExactJson([
        'id' => $version->id,
        'package_id' => $version->package->id,
        'name' => $version->name,
        'metadata' => $version->metadata,
        'shasum' => $version->shasum,
        'created_at' => $version->created_at,
        'updated_at' => $version->updated_at,
    ]);

    expect($version->is($originalVersion))
        ->toBeTrue();
})
    ->with(rootAndSubRepository())
    ->with(providerPushEvents());
