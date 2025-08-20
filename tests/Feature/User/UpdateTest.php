<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Resources\UserResource;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\patchJson;
use function PHPUnit\Framework\assertNotNull;

it('updates', function (?User $user, int $status): void {
    Repository::factory()->create();

    $user = User::factory()->create();

    $user->repositories()->sync([1]);

    $attributes = [
        'name' => $name = fake()->name,
        'email' => $email = fake()->safeEmail,
        'role' => $role = Role::USER,
        'password' => $password = fake()->password,
        'repositories' => [],
    ];

    $response = patchJson("/api/users/$user->id", $attributes)
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(new UserResource(
            $user = $user->fresh()?->load('repositories')
        ))
    );

    assertNotNull($user);

    expect($user)
        ->name->toBe($name)
        ->email->toBe($email)
        ->role->toBe($role)
        ->and($user->repositories->count())->toBe(0)
        ->and(Hash::check($password, $user->password));
})
    ->with(guestAndUsers(Permission::USER_UPDATE));

it('has unique email', function (?User $user, int $status): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $attributes = [
        'email' => $otherUser->email,
    ];

    patchJson("/api/users/$user->id", $attributes)
        ->assertStatus($status)
        ->assertExactJson(validation([
            'email' => ['Email has already been taken.'],
        ]));
})
    ->with(unscopedUser(Permission::USER_UPDATE, expectedStatus: 422));
