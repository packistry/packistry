<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use App\Sources\Importable;

it('creates version for new tag', function (Repository $repository, SourceProvider $provider, ...$args): void {
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
        'name' => '1.0.0',
        'shasum' => $version->shasum,
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
    ->with(providerPushEvents());

it('has correct zip url for tag', function (SourceProvider $provider, Importable $event, ...$rest): void {
    $url = match ($provider) {
        SourceProvider::GITEA => 'https://gitea.com/vendor/test/archive/v1.0.0.zip',
        SourceProvider::GITHUB => 'https://github.com/vendor/test/archive/v1.0.0.zip',
        SourceProvider::GITLAB => 'https://gitlab.com/api/v4/projects/1/repository/archive.zip?sha=checkoutsha',
        SourceProvider::BITBUCKET => 'https://bitbucket.org/vendor/test/get/v1.0.0.zip',
    };

    expect($event->zipUrl())->toBe($url);
})
    ->with(providerPushEvents(
        ref: 'v1.0.0'
    ));

it('has correct zip url for branch', function (SourceProvider $provider, Importable $event, ...$rest): void {
    $url = match ($provider) {
        SourceProvider::GITEA => 'https://gitea.com/vendor/test/archive/feature/my-feature.zip',
        SourceProvider::GITHUB => 'https://github.com/vendor/test/archive/feature/my-feature.zip',
        SourceProvider::GITLAB => 'https://gitlab.com/api/v4/projects/1/repository/archive.zip?sha=checkoutsha',
        SourceProvider::BITBUCKET => 'https://bitbucket.org/vendor/test/get/feature/my-feature.zip',
    };

    expect($event->zipUrl())->toBe($url);
})
    ->with(providerPushEvents(
        refType: 'heads',
        ref: 'feature/my-feature',
    ));
