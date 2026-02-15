<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\TokenAbility;
use App\Models\DeployToken;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\postJson;

it('stores', function (?User $user, int $status): void {
    Repository::factory()->create();
    $response = postJson('/api/deploy-tokens', [
        'name' => $name = fake()->name,
        'abilities' => $abilities = [TokenAbility::REPOSITORY_READ->value],
        'expires_at' => $expiresAt = now()->addMonth()->format(DATE_RFC3339_EXTENDED),
        'repositories' => [
            1,
        ],
    ])
        ->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    $response->assertJsonStructure([
        'token',
        'plain_text',
    ]);

    /** @var DeployToken $token */
    $token = DeployToken::query()->first();

    expect($token)
        ->name->toBe($name)
        ->token->abilities->toBe($abilities)
        ->token->expires_at->format(DATE_RFC3339_EXTENDED)->toBe($expiresAt)
        ->and($token->repositories()->count())->toBe(1);
})
    ->with(guestAndUsers(Permission::DEPLOY_TOKEN_CREATE, userWithPermission: 201));
