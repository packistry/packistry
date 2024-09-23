<?php

declare(strict_types=1);

use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Database\Factories\RepositoryFactory;

it('creates version for new tag', function (Repository $repository, ...$args): void {
    $response = webhook($repository, ...$args)
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
    ->with(rootAndSubRepository(
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state(['name' => 'vendor/test']))
    ))
    ->with(providerPushEvents(
        ref: 'v1.0.0'
    ));

it('overwrites version for same tag', function (Repository $repository, ...$args): void {
    $response = webhook($repository, ...$args)
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
})
    ->with(rootAndSubRepositoryFromZip(
        name: 'vendor/test',
        version: '1.0.0',
        zip: __DIR__.'/../../Fixtures/gitea-jamie-test.zip',
        subDirectory: 'test/'
    ))
    ->with(providerPushEvents());
