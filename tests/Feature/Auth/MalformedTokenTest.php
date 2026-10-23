<?php

declare(strict_types=1);

use App\Models\Package;
use App\Models\Repository;
use Database\Factories\RepositoryFactory;

use function Pest\Laravel\getJson;

it('treats a malformed bearer token as a guest on composer endpoints', function (Repository $repository, string $token): void {
    getJson($repository->url('/p2/test/test.json'), ['Authorization' => "Bearer $token"])
        ->assertNotFound();
})
    ->with(rootAndSubRepository(
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->state([
                    'name' => 'test/test',
                ])
            )
    ))
    ->with([
        'without separator' => 'x',
        'prefix only' => 'pkdt',
        'prefix with empty value' => 'pkdt-',
    ]);

it('treats a malformed bearer token as unauthenticated on api routes', function (string $token): void {
    getJson('/api/dashboard', ['Authorization' => "Bearer $token"])
        ->assertUnauthorized();
})
    ->with([
        'without separator' => 'x',
        'prefix only' => 'pkdt',
        'prefix with empty value' => 'pkdt-',
    ]);
