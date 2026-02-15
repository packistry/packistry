<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\UserResource;
use App\Models\User;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\deleteJson;

it('destroys', function (?User $user, int $status): void {
    $user = User::factory()->create();

    $response = deleteJson("/api/users/$user->id")
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(new UserResource($user))
    );

    assertModelMissing($user);
})
    ->with(guestAndUsers(Permission::USER_DELETE));

it('can not delete self', function (User $user, int $status): void {
    deleteJson("/api/users/$user->id")
        ->assertStatus($status);
})
    ->with(unscopedUser(Permission::USER_DELETE, 403));
