<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\Package;
use App\Models\Repository;
use Database\Factories\RepositoryFactory;
use Illuminate\Contracts\Auth\Authenticatable;

use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotNull;

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
        public: true
    ))
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ));

it('provides urls from private repository', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    getJson($repository->url('/packages.json'))
        ->assertStatus($status);
})
    ->with(rootAndSubRepository())
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_READ,
        guestStatus: 401,
        personalTokenWithoutAccessStatus: 401,
        deployTokenWithoutAccessStatus: 401,
    ));

describe('package-scoped access', function (): void {
    it('allows access with package-scoped token in public repository', function (): void {
        $repository = rootRepository(public: true, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state(['name' => 'vendor/allowed']))
        );

        $package = $repository->packages->first();
        assertNotNull($package);

        deployTokenWithPackageAccess($package, TokenAbility::REPOSITORY_READ);

        getJson($repository->url('/packages.json'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'search',
                'metadata-url',
                'list',
            ]);
    });

    it('allows access with package-scoped token in private repository', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state(['name' => 'vendor/allowed']))
        );

        $package = $repository->packages->first();
        assertNotNull($package);

        deployTokenWithPackageAccess($package, TokenAbility::REPOSITORY_READ);

        getJson($repository->url('/packages.json'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'search',
                'metadata-url',
                'list',
            ]);
    });

    it('denies access for token without repository or package access', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state(['name' => 'vendor/package']))
        );

        // Token with no access
        deployToken(TokenAbility::REPOSITORY_READ);

        getJson($repository->url('/packages.json'))
            ->assertStatus(401);
    });
});
