<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\DeployTokenResource;
use App\Models\DeployToken;
use App\Models\User;

use function Pest\Laravel\getJson;

it('indexes', function (?User $user, int $status): void {
    DeployToken::factory()->count(10)->afterCreating(function (DeployToken $token): void {
        $token->createToken(
            name: fake()->name(),
            abilities: [],
            expiresAt: now()->addDays(30),
        );
    })->create();

    $response = getJson('/api/deploy-tokens')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertJsonPath(
        'data',
        resourceAsJson(
            DeployTokenResource::collection(DeployToken::query()->with('token')->get())
        )
    );
})->with(guestAndUsers(Permission::DEPLOY_TOKEN_READ));

it('searches', function (?User $user, int $status): void {
    $token = DeployToken::factory()->afterCreating(function (DeployToken $token): void {
        $token->createToken(
            name: fake()->name(),
            abilities: [],
            expiresAt: now()->addDays(30),
        );
    })->create();

    $token->load('token');

    getJson("/api/deploy-tokens?filter[search]=$token->name")
        ->assertStatus($status)
        ->assertJsonPath('data', resourceAsJson(DeployTokenResource::collection([$token])));

    getJson('/api/deploy-tokens?filter[search]=something%20else')
        ->assertStatus($status)
        ->assertJsonPath('data', []);
})
    ->with(unscopedUser(Permission::DEPLOY_TOKEN_READ));
