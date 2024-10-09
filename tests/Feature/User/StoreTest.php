<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Resources\UserResource;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\postJson;

it('stores', function (?User $user, int $status, Role $role, array $repositories): void {
    Repository::factory()->create();

    $response = postJson('/users', [
        'name' => $name = fake()->name,
        'email' => $email = fake()->unique()->email,
        'role' => $role,
        'password' => $password = fake()->password,
        'repositories' => $repositories,
    ])
        ->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    /** @var User $user */
    $user = \App\Models\User::query()->find(2);
    $response->assertExactJson(
        resourceAsJson(new UserResource(
            $user,
        ))
    );

    expect($user)
        ->name->toBe($name)
        ->email->toBe($email)
        ->role->toBe($role)
        ->and($user->repositories()->pluck('repositories.id')->toArray())->toBe($repositories)
        ->and(Hash::check($password, $user->password));
})
    ->with(guestAndUsers(Permission::USER_CREATE, userWithPermission: 201))
    ->with([
        'admin' => [
            'role' => Role::ADMIN,
            'repositories' => [],
        ],
        'user' => [
            'role' => Role::USER,
            'repositories' => [1],
        ],
    ]);

it('has unique email', function (?User $user, int $status): void {
    $user = User::factory()->create();

    postJson('/users', [
        'name' => fake()->name,
        'email' => $user->email,
        'role' => Role::USER,
        'password' => fake()->password,
        'repositories' => [],
    ])
        ->assertStatus($status)
        ->assertExactJson(validation([
            'email' => ['Email has already been taken.'],
        ]));
})
    ->with(unscopedUser(Permission::USER_CREATE, expectedStatus: 422));

it('requires valid email', function (?User $user, int $status): void {
    postJson('/users', [
        'name' => fake()->name,
        'email' => fake()->name,
        'role' => Role::USER,
        'password' => fake()->password,
        'repositories' => [],
    ])
        ->assertStatus($status)
        ->assertExactJson(validation([
            'email' => ['The email field must be a valid email address.'],
        ]));
})
    ->with(unscopedUser(Permission::USER_CREATE, expectedStatus: 422));
