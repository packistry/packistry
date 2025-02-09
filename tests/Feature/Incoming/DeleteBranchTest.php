<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
use App\Http\Resources\VersionResource;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;

it('deletes branch', function (Repository $repository, SourceProvider $provider, ...$args): void {
    /** @var Package $package */
    $package = Package::factory()
        ->for($repository)
        ->name('vendor/test')
        ->has(Version::factory()->name('dev-feature-something'))
        ->provider($provider)
        ->create();

    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    webhook($repository, $package->source, ...$args)
        ->assertOk()
        ->assertExactJson(resourceAsJson(new VersionResource($version)));

    expect(Version::query()->count())->toBe(0);
})
    ->with(rootAndSubRepository())
    ->with(providerDeleteEvents(
        refType: 'heads',
        ref: 'feature-something'
    ));
