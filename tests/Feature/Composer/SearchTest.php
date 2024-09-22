<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Enums\PackageType;
use App\Models\Package;
use App\Models\Repository;
use Database\Factories\RepositoryFactory;

use function Pest\Laravel\getJson;

it('searches empty repository', function (Repository $repository): void {
    getJson($repository->url('/search.json'))
        ->assertOk()
        ->assertExactJson([
            'total' => 0,
            'results' => [],
        ]);
})->with(rootAndSubRepository(
    public: true,
));

it('searches filled repository', function (Repository $repository): void {
    getJson($repository->url('/search.json'))
        ->assertOk()
        ->assertJsonContent([
            'total' => 10,
            'results' => $repository->packages->map(fn (Package $package): array => [
                'name' => $package->name,
                'description' => '',
                'downloads' => 0,
            ]),
        ]);
})->with(rootAndSubRepository(
    public: true,
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()->count(10))
));

it('searches by query', function (Repository $repository): void {
    getJson($repository->url('/search.json?q=test'))
        ->assertOk()
        ->assertJsonContent([
            'total' => 1,
            'results' => [
                [
                    'name' => 'test/test',
                    'description' => '',
                    'downloads' => 0,
                ],
            ],
        ]);
})->with(rootAndSubRepository(
    public: true,
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()->state([
            'name' => 'test/test',
        ]))
        ->has(Package::factory()->state([
            'type' => PackageType::LIBRARY,
        ])->count(9))
));

it('searches by type', function (Repository $repository): void {
    Repository::factory()
        ->public()
        ->root()

        ->create();

    getJson($repository->url('/search.json?type=composer-plugin'))
        ->assertOk()
        ->assertJsonContent([
            'total' => 1,
            'results' => [
                [
                    'name' => 'test/test',
                    'description' => '',
                    'downloads' => 0,
                ],
            ],
        ]);
})->with(rootAndSubRepository(
    public: true,
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()->state([
            'name' => 'test/test',
            'type' => PackageType::COMPOSER_PLUGIN,
        ]))
        ->has(Package::factory()->state([
            'type' => PackageType::LIBRARY,
        ])->count(9))
));

it('requires authentication', function (Repository $repository): void {
    getJson($repository->url('/search.json?type=composer-plugin'))
        ->assertUnauthorized();
})->with(rootAndSubRepository());

it('requires ability', function (Repository $repository): void {
    user(Ability::REPOSITORY_READ);

    getJson($repository->url('/search.json?type=composer-plugin'))
        ->assertOk();
})->with(rootAndSubRepository());
