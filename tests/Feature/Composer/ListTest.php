<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use Database\Factories\RepositoryFactory;

use function Pest\Laravel\getJson;

it('lists packages', function (Repository $repository, ?User $user, int $status): void {
    getJson($repository->url('/list.json'))
        ->assertStatus($status)
        ->assertJsonContent([
            'packageNames' => $repository->packages->pluck('name'),
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    ))
    ->with(guestAnd(Ability::REPOSITORY_READ));

it('list packages from private repository', function (Repository $repository, ?User $user, int $status): void {
    getJson($repository->url('/list.json'))
        ->assertStatus($status);
})
    ->with(rootAndSubRepository(
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    ))
    ->with(guestAnd(Ability::REPOSITORY_READ, [401, 200]));
