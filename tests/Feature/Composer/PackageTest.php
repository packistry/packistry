<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Models\Package;
use App\Models\Version;
use Database\Factories\RepositoryFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotNull;

it('lists package versions', function (): void {
    $repository = rootRepository(
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
    );

    $package = $repository->packages->first();

    assertNotNull($package);

    getJson('/p2/test/test.json')
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
});

it('requires authentication', function (): void {
    rootRepository(closure: fn (RepositoryFactory $factory) => $factory
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
        ));

    getJson('/p2/test/test.json')
        ->assertUnauthorized();
});

it('requires ability', function (): void {
    rootRepository(closure: fn (RepositoryFactory $factory) => $factory
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
        ));

    user(Ability::REPOSITORY_READ);
    getJson('/p2/test/test.json')
        ->assertOk();
});

it('lists package versions for sub', function (): void {
    $repository = repository(
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
    );

    $package = $repository->packages->first();

    assertNotNull($package);

    getJson('/sub/p2/test/test.json')
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
});
