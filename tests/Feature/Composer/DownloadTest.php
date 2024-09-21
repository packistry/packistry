<?php

declare(strict_types=1);

use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;

use function Pest\Laravel\getJson;

it('downloads a version', function (): void {
    $zip = __DIR__.'/../../Fixtures/project.zip';

    Repository::factory()
        ->root()
        ->has(
            Package::factory()
                ->state([
                    'name' => 'test/test',
                ])
                ->has(
                    Version::factory()
                        ->fromZip($zip)
                )
        )
        ->create();

    getJson('/test/test/1.0.0')
        ->assertOk()
        ->assertContent((string) file_get_contents($zip));
});
