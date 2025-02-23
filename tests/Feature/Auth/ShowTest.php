<?php

declare(strict_types=1);

use App\Http\Resources\UserResource;
use App\Models\User;

use function Pest\Laravel\getJson;

it('shows authenticated user', function (?User $user, int $status) {
    $response = getJson('/me')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(
            new UserResource($user)
        )
    );
})->with(guestAndUsers([], userWithoutPermission: 200));
