<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\RepositoryResource;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

it('stores', function (?User $user, int $status): void {
    $response = postJson('/repositories', $attributes = [
        'name' => fake()->name,
        'description' => fake()->text,
        'public' => fake()->boolean(),
    ])
        ->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(new RepositoryResource(
            Repository::query()->first()
        ))
    );

    assertDatabaseHas('repositories', $attributes);
})
    ->with(guestAndUsers(Permission::REPOSITORY_CREATE, userWithPermission: 201));

it('has unique name', function (?User $user, int $status): void {
    $repository = Repository::factory()->create();

    postJson('/repositories', [
        'name' => $repository->name,
    ])
        ->assertStatus($status)
        ->assertExactJson(validation([
            'name' => ['Repository name has already been taken.'],
        ]));
})
    ->with(unscopedUser(Permission::REPOSITORY_CREATE, expectedStatus: 422));
