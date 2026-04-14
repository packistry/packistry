<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\RepositoryResource;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

it('stores', function (?User $user, int $status): void {
    $response = postJson('/api/repositories', $attributes = [
        'name' => fake()->name,
        'path' => $path = fake()->sentence,
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

    assertDatabaseHas('repositories', [...$attributes, 'path' => Str::slug($path)]);
})
    ->with(guestAndUsers(Permission::REPOSITORY_CREATE, userWithPermission: 201));

it('has unique path', function (?User $user, int $status): void {
    $repository = Repository::factory()->create();

    postJson('/api/repositories', [
        'name' => $repository->name,
        'path' => $repository->path,
    ])
        ->assertStatus($status)
        ->assertExactJson(validation([
            'path' => ['Repository path has already been taken.'],
        ]));
})
    ->with(unscopedUser(Permission::REPOSITORY_CREATE, expectedStatus: 422));
