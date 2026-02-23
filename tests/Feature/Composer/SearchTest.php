<?php

declare(strict_types=1);

use App\Enums\PackageType;
use App\Enums\TokenAbility;
use App\Models\Package;
use App\Models\Repository;
use Database\Factories\RepositoryFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\getJson;

it('searches empty repository', function (Repository $repository, ?Authenticatable $auth, int $status): void {
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
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ, expiredDeployTokenWithAccessStatus: 200));

it('searches filled repository', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    getJson($repository->url('/search.json'))
        ->assertStatus($status)
        ->assertExactJson([
            'total' => 10,
            'results' => $repository->packages()
                ->orderBy('name')
                ->get()
                ->map(fn (Package $package): array => [
                    'description' => $package->description,
                    'downloads' => $package->total_downloads,
                    'name' => $package->name,
                ]),
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    ))
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ, expiredDeployTokenWithAccessStatus: 200));

it('searches by query', function (Repository $repository, ?Authenticatable $auth, int $status): void {
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
                'type' => PackageType::LIBRARY->value,
            ])->count(9))
    ))
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ, expiredDeployTokenWithAccessStatus: 200));

it('searches by type', function (Repository $repository, ?Authenticatable $auth, int $status): void {
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
                'type' => PackageType::COMPOSER_PLUGIN->value,
            ]))
            ->has(Package::factory()->state([
                'type' => PackageType::LIBRARY->value,
            ])->count(9))
    ))
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ, expiredDeployTokenWithAccessStatus: 200));

it('searches private from private repository', function (Repository $repository, ?Authenticatable $auth, int $status, ?array $allowedPackages): void {
    $expectedTotal = is_null($allowedPackages)
        ? 5
        : count($allowedPackages);

    getJson($repository->url('/search.json?type=composer-plugin'))
        ->assertStatus($status)
        ->when($status === 200, fn (TestResponse $response) => $response->assertJsonPath('total', $expectedTotal));
})
    ->with(rootAndSubRepository(
        closure: fn (RepositoryFactory $factory) => $factory->withPackages(count: 5, type: 'composer-plugin'),
    ))
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_READ,
        guestStatus: 404,
        personalTokenWithoutAccessStatus: 404,
        deployTokenWithoutAccessStatus: 404,
        deployTokenWithoutPackagesStatus: 404,
        deployTokenPackages: [1, 2],
    ));
