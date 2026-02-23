<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Resources\UserResource;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\postJson;

it('stores', function (?User $user, int $status, Role $role, array $repositories, array $packages): void {
    $repository = Repository::factory()->create();
    Package::factory()->for($repository)->create();

    $response = postJson('/api/users', [
        'name' => $name = fake()->name,
        'email' => $email = fake()->unique()->email,
        'role' => $role,
        'password' => $password = fake()->password,
        'repositories' => $repositories,
        'packages' => $packages,
    ])
        ->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    /** @var User $user */
    $user = User::query()->find(2);
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
        ->and($user->packages()->pluck('packages.id')->toArray())->toBe($packages)
        ->and(Hash::check($password, $user->password));
})
    ->with(guestAndUsers(Permission::USER_CREATE, userWithPermission: 201))
    ->with([
        'admin' => [
            'role' => Role::ADMIN,
            'repositories' => [],
            'packages' => [],
        ],
        'user' => [
            'role' => Role::USER,
            'repositories' => [1],
            'packages' => [1],
        ],
    ]);

it('has unique email', function (?User $user, int $status): void {
    $user = User::factory()->create();

    postJson('/api/users', [
        'name' => fake()->name,
        'email' => $user->email,
        'role' => Role::USER,
        'password' => fake()->password,
        'repositories' => [],
        'packages' => [],
    ])
        ->assertStatus($status)
        ->assertExactJson(validation([
            'email' => ['Email has already been taken.'],
        ]));
})
    ->with(unscopedUser(Permission::USER_CREATE, expectedStatus: 422));

it('requires valid email', function (?User $user, int $status): void {
    postJson('/api/users', [
        'name' => fake()->name,
        'email' => fake()->name,
        'role' => Role::USER,
        'password' => fake()->password,
        'repositories' => [],
        'packages' => [],
    ])
        ->assertStatus($status)
        ->assertExactJson(validation([
            'email' => ['The email field must be a valid email address.'],
        ]));
})
    ->with(unscopedUser(Permission::USER_CREATE, expectedStatus: 422));
