<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\User;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\deleteJson;

it('destroys', function (?User $user, int $status): void {
    if (! $user instanceof User) {
        return;
    }

    $token = $user->createToken('name');

    deleteJson("/personal-tokens/{$token->accessToken->id}")
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    assertModelMissing($token->accessToken);
})
    ->with(guestAndUsers(Permission::PERSONAL_TOKEN_DELETE));
