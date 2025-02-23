<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\UserResource;
use App\Models\User;

use function Pest\Laravel\getJson;

it('indexes', function (?User $user, int $status): void {
    User::factory()->count(8)->create();

    $response = getJson('/users')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertJsonPath(
        'data',
        resourceAsJson(
            UserResource::collection(User::query()->with('repositories', 'authenticationSource')->get()),
        ),
    );

})->with(guestAndUsers(Permission::USER_READ));

it('searches', function (?User $user, int $status): void {
    $user = User::factory()
        ->state([
            'name' => $name = fake()->name,
            'email' => $email = fake()->email,
        ])
        ->create();

    $user->load('repositories', 'authenticationSource');

    getJson("/users?filter[search]=$name")
        ->assertStatus($status)
        ->assertJsonPath('data', resourceAsJson(UserResource::collection([$user])));

    getJson("/users?filter[search]=$email")
        ->assertStatus($status)
        ->assertJsonPath('data', resourceAsJson(UserResource::collection([$user])));

    getJson('/users?filter[search]=something%20else')
        ->assertStatus($status)
        ->assertJsonPath('data', []);
})
    ->with(unscopedUser(Permission::USER_READ));
