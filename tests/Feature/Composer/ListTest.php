<?php

declare(strict_types=1);

use App\Models\Package;
use App\Models\Repository;

use function Pest\Laravel\getJson;

it('lists packages', function (): void {
    /** @var Repository $repository */
    $repository = Repository::factory()
        ->root()
        ->has(Package::factory()->count(10))
        ->create();

    getJson('/list.json')
        ->assertOk()
        ->assertJsonContent([
            'packageNames' => $repository->packages->pluck('name'),
        ]);
});
