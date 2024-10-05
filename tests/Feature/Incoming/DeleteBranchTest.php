<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
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
    ->with(providerDeleteEvents(
        refType: 'heads',
        ref: 'feature-something'
    ));
