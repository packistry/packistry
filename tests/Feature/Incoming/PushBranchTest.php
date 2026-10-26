<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
use App\Http\Resources\VersionResource;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;

it('creates prefixed dev version for new branch (feature -> dev-feature)', function (Repository $repository, SourceProvider $provider, ...$args): void {
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

    $response->assertExactJson(resourceAsJson(new VersionResource($version)));

    expect($version)->name->toBe('dev-feature');
})
    ->with(rootAndSubRepository())
    ->with(providerPushEvents(
        refType: 'heads',
        ref: 'feature',
    ));

it('creates suffixed dev version for new version branch (7.3 -> 7.3.x-dev)', function (Repository $repository, SourceProvider $provider, ...$args): void {
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

    $response->assertExactJson(resourceAsJson(new VersionResource($version)));

    expect($version)->name->toBe('7.3.x-dev');
})
    ->with(rootAndSubRepository())
    ->with(providerPushEvents(
        refType: 'heads',
        ref: '7.3',
    ));

it('creates suffixed dev version for new version branch (7.3.x -> 7.3.x-dev)', function (Repository $repository, SourceProvider $provider, ...$args): void {
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

    $response->assertExactJson(resourceAsJson(new VersionResource($version)));

    expect($version)->name->toBe('7.3.x-dev');
})
    ->with(rootAndSubRepository())
    ->with(providerPushEvents(
        refType: 'heads',
        ref: '7.3.x',
    ));

it('creates suffixed dev version for new version branch (v3 -> v3.x-dev)', function (Repository $repository, SourceProvider $provider, ...$args): void {
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

    $response->assertExactJson(resourceAsJson(new VersionResource($version)));

    expect($version)->name->toBe('v3.x-dev');
})
    ->with(rootAndSubRepository())
    ->with(providerPushEvents(
        refType: 'heads',
        ref: 'v3',
    ));

it('creates dev version for correct repository', function (Repository $repository, SourceProvider $provider, ...$args): void {
    $otherRepo = Repository::factory()->create();

    /** @var Package $otherPackage */
    $otherPackage = Package::factory()
        ->for($otherRepo)
        ->name('vendor/test')
        ->provider($provider)
        ->create();

    /** @var Package $package */
    $package = Package::factory()
        ->for($repository)
        ->name('vendor/test')
        ->state([
            'provider_id' => $otherPackage->provider_id,
            'source_id' => $otherPackage->source_id,
        ])
        ->create();

    $response = webhook($repository, $package->source, ...$args)
        ->assertCreated();

    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    $response->assertExactJson(resourceAsJson(new VersionResource($version)));

    expect($version->package_id)->toBe($package->id);
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

    $response->assertExactJson(resourceAsJson(new VersionResource($version)));

    expect($version->is($originalVersion))
        ->toBeTrue();
})
    ->with(rootAndSubRepository())
    ->with(providerPushEvents(
        refType: 'heads',
        ref: 'feature'
    ));
