<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Http\Resources\UserResource;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\patchJson;
use function PHPUnit\Framework\assertNotNull;

it('updates', function (?User $user, int $status): void {
    Repository::factory()->create();

    $attributes = [
        'name' => $name = fake()->name,
        'email' => $email = fake()->safeEmail,
        'role' => Role::ADMIN,
        'current_password' => 'password',
        'password' => $password = fake()->password,
        'password_confirmation' => $password,
        'repositories' => [
            1,
        ],
    ];

    $response = patchJson('/api/me', $attributes)
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(new UserResource(
            $user = $user?->fresh()
        ))
    );

    assertNotNull($user);

    expect($user)
        ->name->toBe($name)
        ->email->not()->toBe($email)
        ->role->toBe(Role::USER)
        ->and($user->repositories->count())->toBe(0)
        ->and(Hash::check($password, $user->password));
})
    ->with(guestAndUsers([], userWithoutPermission: 200));
