<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Database\Factories\RepositoryFactory;

use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotNull;

it('lists package versions', function (Repository $repository): void {
    $package = $repository->packages->first();
    assertNotNull($package);

    getJson($repository->url('/p2/test/test~dev.json'))
        ->assertOk()
        ->assertJsonContent([
            'minified' => 'composer/2.0',
            'packages' => [
                $package->name => $package->versions()
                    ->where('name', 'like', 'dev-%')
                    ->get()
                    ->map(fn (Version $version) => [
                        ...$version->metadata,
                        'name' => $package->name,
                        'version' => $version->name,
                        'type' => 'library',
                        'time' => $version->created_at,
                        'dist' => [
                            'type' => 'zip',
                            'url' => url("$package->name/$version->name"),
                            'shasum' => $version->shasum,
                        ],
                    ]),
            ],
        ]);
})->with(rootAndSubRepository(
    public: true,
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(
            Package::factory()
                ->name('test/test')
                ->versions(10)
                ->devVersions(10)
        )
));

it('requires authentication', function (Repository $repository): void {
    getJson($repository->url('/p2/test/test~dev.json'))
        ->assertUnauthorized();
})->with(rootAndSubRepository(
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()
            ->state([
                'name' => 'test/test',
            ])
        )
));

it('requires ability', function (Repository $repository): void {
    user(Ability::REPOSITORY_READ);

    getJson($repository->url('/p2/test/test~dev.json'))
        ->assertOk();
})->with(rootAndSubRepository(
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()
            ->state([
                'name' => 'test/test',
            ])
        )
));
