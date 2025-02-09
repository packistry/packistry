<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
use App\Http\Resources\VersionResource;
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

    webhook($repository, $package->source, ...$args)
        ->assertOk()
        ->assertExactJson(resourceAsJson(new VersionResource($version)));

    expect(Version::query()->count())->toBe(0);
})
    ->with(rootAndSubRepository())
    ->with(providerDeleteEvents());
