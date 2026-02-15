<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\Repository;
use Database\Factories\RepositoryFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;

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
            ->withPackages(count: 10)
    ))
    ->with(guestAndTokens(
        TokenAbility::REPOSITORY_READ,
        deployTokenPackages: [1, 2, 3],
        expiredDeployTokenWithAccessStatus: 200,
    ));

it('list packages from private repository', function (Repository $repository, ?Authenticatable $auth, int $status, ?array $allowedPackages): void {
    getJson($repository->url('/list.json'))
        ->assertStatus($status)
        ->when($status === 200, function (TestResponse $response) use ($allowedPackages, $repository) {
            $packages = $repository->packages
                ->unless(is_null($allowedPackages), fn (Collection $packages) => $packages->whereIn('id', $allowedPackages))
                ->pluck('name');

            $response->assertExactJson([
                'packageNames' => $packages,
            ]);
        });
})
    ->with(rootAndSubRepository(
        closure: fn (RepositoryFactory $factory) => $factory
            ->withPackages(count: 10)
    ))
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_READ,
        guestStatus: 401,
        personalTokenWithoutAccessStatus: 401,
        deployTokenWithoutAccessStatus: 401,
        deployTokenWithoutPackagesStatus: 401,
        deployTokenPackages: [1, 2],
    ));
