<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\RepositoryResource;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patchJson;

it('updates', function (?User $user, int $status): void {
    $repository = Repository::factory()->create();

    $attributes = [
        'name' => fake()->name,
        'description' => fake()->text,
        'public' => fake()->boolean(),
    ];

    $response = patchJson("/repositories/$repository->id", $attributes)
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(new RepositoryResource(
            $repository->fresh()
        ))
    );

    assertDatabaseHas('repositories', $attributes);
})
    ->with(guestAndUsers([Permission::REPOSITORY_UPDATE, Permission::UNSCOPED]));

it('has unique name', function (?User $user, int $status): void {
    $repository = Repository::factory()->create();
    $otherRepository = Repository::factory()->create();

    $attributes = [
        'name' => $otherRepository->name,
    ];

    patchJson("/repositories/$repository->id", $attributes)
        ->assertStatus($status)
        ->assertExactJson(validation([
            'name' => ['Repository name has already been taken.'],
        ]));
})
    ->with(unscopedUser(Permission::REPOSITORY_UPDATE, expectedStatus: 422));
