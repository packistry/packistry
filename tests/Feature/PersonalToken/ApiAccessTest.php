<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\TokenAbility;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

/**
 * @return array<string, string>
 */
function bearer(User $user, TokenAbility ...$abilities): array
{
    // the guard keeps the user of the previous request
    Auth::forgetGuards();

    $abilities = array_map(fn (TokenAbility $ability): string => $ability->value, $abilities);

    return [
        'Authorization' => 'Bearer '.$user->createToken('token', $abilities)->plainTextToken,
    ];
}

it('does not give personal tokens without the api ability access to the api', function (TokenAbility ...$abilities): void {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    getJson('/api/me', bearer($admin, ...$abilities))->assertForbidden();
    getJson('/api/users', bearer($admin, ...$abilities))->assertForbidden();
})->with([
    'read' => [TokenAbility::REPOSITORY_READ],
    'write' => [TokenAbility::REPOSITORY_WRITE],
    'read and write' => [TokenAbility::REPOSITORY_READ, TokenAbility::REPOSITORY_WRITE],
]);

it('gives personal tokens with the api ability the permissions of their user', function (): void {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $user = User::factory()->create(['role' => Role::USER]);

    getJson('/api/users', bearer($admin, TokenAbility::API))->assertOk();

    getJson('/api/me', bearer($user, TokenAbility::API))->assertOk();
    getJson('/api/users', bearer($user, TokenAbility::API))->assertForbidden();
});

it('does not give the api ability repository access', function (): void {
    $user = User::factory()->create(['role' => Role::USER]);
    $repository = repository();
    $user->repositories()->sync([$repository->id]);

    getJson($repository->url('/packages.json'), bearer($user, TokenAbility::API))->assertUnauthorized();
    getJson($repository->url('/packages.json'), bearer($user, TokenAbility::REPOSITORY_READ))->assertOk();
});

it('does not give deploy tokens access to the api', function (): void {
    deployToken([TokenAbility::REPOSITORY_READ, TokenAbility::REPOSITORY_WRITE]);

    getJson('/api/me')->assertForbidden();
});

it('does not create deploy tokens with the api ability', function (): void {
    user(Permission::DEPLOY_TOKEN_CREATE);

    postJson('/api/deploy-tokens', [
        'name' => 'CI',
        'abilities' => [TokenAbility::API->value],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['abilities.0']);
});

it('keeps the session working', function (): void {
    $admin = User::factory()->create(['role' => Role::ADMIN]);

    postJson('/api/login', ['email' => $admin->email, 'password' => 'password'])->assertOk();

    getJson('/api/users')->assertOk();
});
