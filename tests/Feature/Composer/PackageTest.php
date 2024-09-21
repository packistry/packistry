<?php

declare(strict_types=1);

use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotNull;

it('lists package versions', function (): void {
    /** @var Repository $repository */
    $repository = Repository::factory()
        ->root()
        ->has(
            Package::factory()
                ->has(Version::factory()
                    ->state(new Sequence(
                        fn (Sequence $sequence) => ['name' => '0.1.'.$sequence->index],
                    ))
                    ->count(10)
                )
                ->state([
                    'name' => 'test/test',
                ])
        )
        ->create();

    $package = $repository->packages->first();

    assertNotNull($package);

    getJson('/p2/test/test.json')
        ->assertOk()
        ->assertContent((string) json_encode([
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
        ]));
});
