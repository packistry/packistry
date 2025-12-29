<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\Repository;
use Database\Factories\RepositoryFactory;
use Illuminate\Contracts\Auth\Authenticatable;

use function Pest\Laravel\getJson;

it('provides urls', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    $prefix = is_null($repository->path) ? '' : "/r/$repository->path/";

    getJson($repository->url('/packages.json'))
        ->assertStatus($status)
        ->assertExactJson([
            'search' => url($prefix.'search.json?q=%query%&type=%type%'),
            'metadata-url' => url($prefix.'p2/%package%.json'),
            'list' => url($prefix.'list.json'),
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory->withPackages(count: 5)
    ))
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_READ,
        deployTokenPackages: [1, 2],
    ));

it('provides urls from private repository', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    getJson($repository->url('/packages.json'))
        ->assertStatus($status);
})
    ->with(rootAndSubRepository(
        closure: fn (RepositoryFactory $factory) => $factory->withPackages(count: 5),
    ))
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_READ,
        guestStatus: 401,
        personalTokenWithoutAccessStatus: 401,
        deployTokenWithoutAccessStatus: 401,
        deployTokenPackages: [1, 2, 3],
    ));
