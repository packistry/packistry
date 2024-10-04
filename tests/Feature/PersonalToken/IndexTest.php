<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\PersonalTokenResource;
use App\Models\Token;
use App\Models\User;

use function Pest\Laravel\getJson;

it('indexes', function (?User $user, int $status): void {
    if (! $user instanceof \App\Models\User) {
        return;
    }

    $user->createToken('name');
    $user->createToken('name 2');

    $response = getJson('/personal-tokens')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertJsonPath(
        'data',
        resourceAsJson(
            PersonalTokenResource::collection(Token::query()->get())
        )
    );
})->with(guestAndUsers(Permission::PERSONAL_TOKEN_READ));

it('searches', function (?User $user, int $status): void {
    if (! $user instanceof \App\Models\User) {
        return;
    }

    $token = $user->createToken('name');

    getJson('/personal-tokens?filter[search]=name')
        ->assertStatus($status)
        ->assertJsonPath('data', resourceAsJson(PersonalTokenResource::collection([$token->accessToken])));

    getJson('/personal-tokens?filter[search]=something%20else')
        ->assertStatus($status)
        ->assertJsonPath('data', []);
})
    ->with(unscopedUser(Permission::PERSONAL_TOKEN_READ));
