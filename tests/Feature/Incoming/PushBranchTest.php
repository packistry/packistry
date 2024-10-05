<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;

it('creates dev version for new branch', function (Repository $repository, SourceProvider $provider, ...$args): void {
    /** @var Package $package */
    $package = Package::factory()
        ->for($repository)
        ->name('vendor/test')
        ->provider($provider)
        ->create();

    $response = webhook($repository, $package->source, ...$args)
        ->assertCreated();

    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    $response->assertExactJson([
        'package_id' => $version->package->id,
        'name' => $version->name,
        'shasum' => $version->shasum,
        'updated_at' => $version->updated_at,
        'created_at' => $version->created_at,
        'id' => $version->id,
    ]);
})
    ->with(rootAndSubRepository())
    ->with(providerPushEvents(
        refType: 'heads'
    ));

it('overwrites dev version for same branch', function (Repository $repository, SourceProvider $provider, ...$args): void {
    $package = Package::factory()
        ->name('vendor/test')
        ->for($repository)
        ->provider($provider)
        ->create();

    $originalVersion = Version::factory()
        ->for($package)
        ->fromDefaultZip(
            version: 'dev-feature'
        )
        ->create();

    $response = webhook($repository, $package->source, ...$args)
        ->assertCreated();

    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    $response->assertExactJson([
        'id' => $version->id,
        'package_id' => $version->package->id,
        'name' => $version->name,
        'shasum' => $version->shasum,
        'created_at' => $version->created_at,
        'updated_at' => $version->updated_at,
    ]);

    expect($version->is($originalVersion))
        ->toBeTrue();
})
    ->with(rootAndSubRepository())
    ->with(providerPushEvents(
        refType: 'heads',
        ref: 'feature'
    ));
