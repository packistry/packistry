<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Models\Package;
use App\Models\Repository;
use Database\Factories\RepositoryFactory;

use function Pest\Laravel\getJson;

it('lists packages', function (Repository $repository): void {
    getJson($repository->url('/list.json'))
        ->assertOk()
        ->assertJsonContent([
            'packageNames' => $repository->packages->pluck('name'),
        ]);
})->with(rootAndSubRepository(
    public: true,
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()->count(10))
));

it('requires authentication', function (Repository $repository): void {
    getJson($repository->url('/list.json'))
        ->assertUnauthorized();
})->with(rootAndSubRepository(
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()->count(10))
));

it('requires ability', function (Repository $repository): void {
    user(Ability::REPOSITORY_READ);
    getJson($repository->url('/list.json'))
        ->assertOk();
})->with(rootAndSubRepository(
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()->count(10))
));
