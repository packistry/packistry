<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\Package;
use App\Models\Repository;
use Database\Factories\RepositoryFactory;
use Illuminate\Contracts\Auth\Authenticatable;

use function Pest\Laravel\getJson;

it('lists packages', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    getJson($repository->url('/list.json'))
        ->assertStatus($status)
        ->assertExactJson([
            'packageNames' => $repository->packages->pluck('name'),
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    ))
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ));

it('list packages from private repository', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    getJson($repository->url('/list.json'))
        ->assertStatus($status);
})
    ->with(rootAndSubRepository(
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    ))
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_READ,
        guestStatus: 401,
        personalTokenWithoutAccessStatus: 401,
        deployTokenWithoutAccessStatus: 401,
    ));
