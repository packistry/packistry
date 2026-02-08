<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Database\Factories\RepositoryFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotNull;

it('lists package versions', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    $package = $repository->packages->first();

    assertNotNull($package);

    getJson($repository->url('/p2/test/test.json'))
        ->assertStatus($status)
        ->assertExactJson([
            'minified' => 'composer/2.0',
            'packages' => [
                $package->name => $package->versions()
                    ->where('name', 'not like', 'dev-%')
                    ->where('name', 'not like', '%-dev')
                    ->get()
                    ->map(fn (Version $version) => [
                        ...$version->metadata,
                        'name' => $package->name,
                        'version' => $version->name,
                        'type' => $package->type,
                        'time' => $version->created_at,
                        'dist' => [
                            'type' => 'zip',
                            'url' => $package->repository->url("/$package->name/$version->name"),
                            'shasum' => $version->shasum,
                        ],
                    ])->toArray(),
            ],
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()
                    ->state(new Sequence(
                        fn (Sequence $sequence): array => ['name' => '0.1.'.$sequence->index],
                    ))
                    ->count(10)
                )
                ->devVersions(10)
                ->state([
                    'name' => 'test/test',
                ])
            )
    ))
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ));

it('lists package versions when name includes dots', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    $package = $repository->packages->first();

    assertNotNull($package);

    getJson($repository->url('/p2/steelants/laravel-boilerplate.warehouse.json'))
        ->assertStatus($status)
        ->assertExactJson([
            'minified' => 'composer/2.0',
            'packages' => [
                $package->name => $package->versions()
                    ->where('name', 'not like', 'dev-%')
                    ->where('name', 'not like', '%-dev')
                    ->get()
                    ->map(fn (Version $version) => [
                        ...$version->metadata,
                        'name' => $package->name,
                        'version' => $version->name,
                        'type' => $package->type,
                        'time' => $version->created_at,
                        'dist' => [
                            'type' => 'zip',
                            'url' => $package->repository->url("/$package->name/$version->name"),
                            'shasum' => $version->shasum,
                        ],
                    ])->toArray(),
            ],
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()
                    ->state(new Sequence(
                        fn (Sequence $sequence): array => ['name' => '0.1.'.$sequence->index],
                    ))
                    ->count(3)
                )
                ->devVersions(2)
                ->state([
                    'name' => 'steelants/laravel-boilerplate.warehouse',
                ])
            )
    ))
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ));

it('requires ability', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    getJson($repository->url('/p2/test/test.json'))
        ->assertStatus($status);
})
    ->with(rootAndSubRepository(
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->state([
                    'name' => 'test/test',
                ])
            )
    ))
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_READ,
        guestStatus: 401,
        personalTokenWithoutAccessStatus: 401,
        deployTokenWithoutAccessStatus: 401,
    ));
