<?php

declare(strict_types=1);

use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Database\Factories\RepositoryFactory;

it('creates dev version for new branch', function (Repository $repository, ...$args): void {
    $response = webhook($repository, ...$args)
        ->assertOk();

    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    $response->assertExactJson([
        'package_id' => $version->package->id,
        'name' => $version->name,
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
        refType: 'heads'
    ));

it('overwrites dev version for same branch', function (Repository $repository, ...$args): void {
    /** @var Version $originalVersion */
    $originalVersion = Version::query()->latest('id')->first();

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

    expect($version->is($originalVersion))
        ->toBeTrue();
})
    ->with(rootAndSubRepositoryFromZip(
        name: 'vendor/test',
        version: 'dev-feature',
        zip: __DIR__.'/../../Fixtures/gitea-jamie-test.zip',
        subDirectory: 'test/'
    ))
    ->with(providerPushEvents(
        refType: 'heads',
        ref: 'feature'
    ));
