<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Enums\PackageType;
use App\Models\Package;
use App\Models\Repository;
use Database\Factories\RepositoryFactory;

use function Pest\Laravel\getJson;

it('searches empty repository', function (): void {
    rootRepository(public: true);

    getJson('/search.json')
        ->assertOk()
        ->assertExactJson([
            'total' => 0,
            'results' => [],
        ]);
});

it('searches filled repository', function (): void {
    $repository = rootRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    );

    getJson('/search.json')
        ->assertOk()
        ->assertJsonContent([
            'total' => 10,
            'results' => $repository->packages->map(fn (Package $package): array => [
                'name' => $package->name,
                'description' => '',
                'downloads' => 0,
            ]),
        ]);
});

it('searches by query', function (): void {
    rootRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state([
                'name' => 'test/test',
            ]))
            ->has(Package::factory()->state([
                'type' => PackageType::LIBRARY,
            ])->count(9))
    );

    getJson('/search.json?q=test')
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
});

it('searches by type', function (): void {
    rootRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state([
                'name' => 'test/test',
                'type' => PackageType::COMPOSER_PLUGIN,
            ]))
            ->has(Package::factory()->state([
                'type' => PackageType::LIBRARY,
            ])->count(9))
    );

    Repository::factory()
        ->public()
        ->root()

        ->create();

    getJson('/search.json?type=composer-plugin')
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
});

it('requires authentication', function (): void {
    rootRepository();

    getJson('/search.json?type=composer-plugin')
        ->assertUnauthorized();
});

it('requires ability', function (): void {
    rootRepository();

    user(Ability::REPOSITORY_READ);
    getJson('/search.json?type=composer-plugin')
        ->assertOk();
});

it('searches sub repository', function (): void {
    $repository = repository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    );

    getJson('/sub/search.json')
        ->assertOk()
        ->assertJsonContent([
            'total' => 10,
            'results' => $repository->packages->map(fn (Package $package): array => [
                'name' => $package->name,
                'description' => '',
                'downloads' => 0,
            ]),
        ]);
});
