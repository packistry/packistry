<?php

declare(strict_types=1);

use App\Models\Repository;

use function Pest\Laravel\getJson;

it('searches repository', function (): void {
    Repository::factory()
        ->root()
        ->create();

    getJson('/search.json')
        ->assertOk()
        ->assertExactJson([
            'results' => [],
            'total' => 0,
        ]);
});
