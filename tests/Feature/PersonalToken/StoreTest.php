<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\TokenAbility;
use App\Models\Repository;
use App\Models\Token;
use App\Models\User;

use function Pest\Laravel\freezeSecond;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertNotNull;

it('stores', function (?User $user, int $status): void {
    freezeSecond();

    Repository::factory()->create();
    $response = postJson('/personal-tokens', [
        'name' => $name = fake()->name,
        'abilities' => $abilities = [TokenAbility::REPOSITORY_READ->value],
        'expires_at' => $expiresAt = now()->addMonth()->format(DATE_RFC3339_EXTENDED),
    ])
        ->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    $response->assertJsonStructure([
        'token',
        'plain_text',
    ]);

    assertNotNull($user);

    $user->repositories()->sync([1]);
    /** @var Token $token */
    $token = Token::query()->first();

    expect($token)
        ->name->toBe($name)
        ->abilities->toBe($abilities)
        ->expires_at->format(DATE_RFC3339_EXTENDED)->toBe($expiresAt);
})
    ->with(guestAndUsers(Permission::PERSONAL_TOKEN_CREATE, userWithPermission: 201));
