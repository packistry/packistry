<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Models\Package;
use Database\Factories\RepositoryFactory;

use function Pest\Laravel\getJson;

it('lists packages', function (): void {
    $repository = rootRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    );

    getJson('/list.json')
        ->assertOk()
        ->assertJsonContent([
            'packageNames' => $repository->packages->pluck('name'),
        ]);
});

it('lists sub repository packages', function (): void {
    $repository = repository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    );

    getJson('/sub/list.json')
        ->assertOk()
        ->assertJsonContent([
            'packageNames' => $repository->packages->pluck('name'),
        ]);
});

it('requires authentication', function (): void {
    rootRepository();

    getJson('/list.json')
        ->assertUnauthorized();
});

it('requires ability', function (): void {
    rootRepository();

    user(Ability::REPOSITORY_READ);
    getJson('/list.json')
        ->assertOk();
});
