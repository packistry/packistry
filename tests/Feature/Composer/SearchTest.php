<?php

declare(strict_types=1);

use App\Enums\PackageType;
use App\Models\Package;
use App\Models\Repository;

use function Pest\Laravel\getJson;

it('searches empty repository', function (): void {
    Repository::factory()
        ->root()
        ->create();

    getJson('/search.json')
        ->assertOk()
        ->assertExactJson([
            'total' => 0,
            'results' => [],
        ]);
});

it('searches filled repository', function (): void {
    $repository = Repository::factory()
        ->root()
        ->has(Package::factory()->count(10))
        ->create();

    getJson('/search.json')
        ->assertOk()
        ->assertJsonContent([
            'total' => 10,
            'results' => $repository->packages->map(fn (Package $package) => [
                'name' => $package->name,
                'description' => '',
                'downloads' => 0,
            ]),
        ]);
});

it('searches by query', function (): void {
    Repository::factory()
        ->root()
        ->has(Package::factory()->state([
            'name' => 'test/test',
        ]))
        ->has(Package::factory()->count(9))
        ->create();

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
    Repository::factory()
        ->root()
        ->has(Package::factory()->state([
            'name' => 'test/test',
            'type' => PackageType::COMPOSER_PLUGIN,
        ]))
        ->has(Package::factory()->state(['type' => PackageType::LIBRARY])->count(9))
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
