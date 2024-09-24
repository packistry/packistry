<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Enums\PackageType;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use Database\Factories\RepositoryFactory;

use function Pest\Laravel\getJson;

it('searches empty repository', function (Repository $repository, ?User $user, int $status): void {
    getJson($repository->url('/search.json'))
        ->assertStatus($status)
        ->assertExactJson([
            'total' => 0,
            'results' => [],
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
    ))
    ->with(guestAnd(Ability::REPOSITORY_READ));

it('searches filled repository', function (Repository $repository, ?User $user, int $status): void {
    getJson($repository->url('/search.json'))
        ->assertStatus($status)
        ->assertExactJson([
            'total' => 10,
            'results' => $repository->packages()
                ->orderBy('name')
                ->get()
                ->map(fn (Package $package): array => [
                    'description' => $package->description,
                    'downloads' => $package->downloads,
                    'name' => $package->name,
                ]),
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    ))
    ->with(guestAnd(Ability::REPOSITORY_READ));

it('searches by query', function (Repository $repository, ?User $user, int $status): void {
    getJson($repository->url('/search.json?q=test'))
        ->assertStatus($status)
        ->assertExactJson([
            'total' => 1,
            'results' => [
                [
                    'name' => 'test/test',
                    'description' => null,
                    'downloads' => 0,
                ],
            ],
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state([
                'name' => 'test/test',
            ]))
            ->has(Package::factory()->state([
                'type' => PackageType::LIBRARY,
            ])->count(9))
    ))
    ->with(guestAnd(Ability::REPOSITORY_READ));

it('searches by type', function (Repository $repository, ?User $user, int $status): void {
    Repository::factory()
        ->public()
        ->root()

        ->create();

    getJson($repository->url('/search.json?type=composer-plugin'))
        ->assertStatus($status)
        ->assertExactJson([
            'total' => 1,
            'results' => [
                [
                    'name' => 'test/test',
                    'description' => null,
                    'downloads' => 0,
                ],
            ],
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state([
                'name' => 'test/test',
                'type' => PackageType::COMPOSER_PLUGIN,
            ]))
            ->has(Package::factory()->state([
                'type' => PackageType::LIBRARY,
            ])->count(9))
    ))
    ->with(guestAnd(Ability::REPOSITORY_READ));

it('searches private from private repository', function (Repository $repository, ?User $user, int $status): void {
    getJson($repository->url('/search.json?type=composer-plugin'))
        ->assertStatus($status);
})
    ->with(rootAndSubRepository())
    ->with(guestAnd(Ability::REPOSITORY_READ, [401, 200]));
