<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Database\Factories\RepositoryFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotNull;

it('lists package versions', function (Repository $repository): void {
    $package = $repository->packages->first();

    assertNotNull($package);

    getJson($repository->url('/p2/test/test.json'))
        ->assertOk()
        ->assertJsonContent([
            'minified' => 'composer/2.0',
            'packages' => [
                $package->name => $package->versions->map(fn (Version $version) => [
                    ...$version->metadata,
                    'name' => $package->name,
                    'version' => $version->name,
                    'type' => 'library',
                    'time' => $version->created_at,
                    'dist' => [
                        'type' => 'zip',
                        'url' => url("$package->name/$version->name"),
                        'shasum' => $version->shasum,
                    ],
                ]),
            ],
        ]);
})->with(rootAndSubRepository(
    public: true,
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()
            ->has(Version::factory()
                ->state(new Sequence(
                    fn (Sequence $sequence): array => ['name' => '0.1.'.$sequence->index],
                ))
                ->count(10)
            )
            ->state([
                'name' => 'test/test',
            ])
        )
));

it('requires authentication', function (Repository $repository): void {
    getJson($repository->url('/p2/test/test.json'))
        ->assertUnauthorized();
})->with(rootAndSubRepository(
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()
            ->state([
                'name' => 'test/test',
            ])
        )
));

it('requires ability', function (Repository $repository): void {
    user(Ability::REPOSITORY_READ);
    getJson($repository->url('/p2/test/test.json'))
        ->assertOk();
})->with(rootAndSubRepository(
    closure: fn (RepositoryFactory $factory) => $factory
        ->has(Package::factory()
            ->state([
                'name' => 'test/test',
            ])
        )
));
