<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withCookie;

function login(User $user): string
{
    $response = postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();

    Auth::forgetGuards();

    return sessionId($response);
}

function sessionId(TestResponse $response): string
{
    return (string) $response->getCookie(config()->string('session.cookie'))?->getValue();
}

function withSession(string $session): void
{
    Auth::forgetGuards();

    withCookie(config()->string('session.cookie'), $session);
}

it('logs out other sessions when the password changes', function (): void {
    $user = User::factory()->create();
    $session = login($user);

    withSession($session);
    getJson('/api/me')->assertOk();

    $user->forceFill(['password' => 'changed-elsewhere'])->save();

    withSession($session);
    getJson('/api/me')->assertUnauthorized();
});

it('keeps the current session when changing its own password', function (): void {
    $user = User::factory()->create();
    $session = login($user);

    withSession($session);
    patchJson('/api/me', [
        'name' => $user->name,
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk();

    withSession($session);
    getJson('/api/me')->assertOk();
});

it('invalidates the session on logout', function (): void {
    $user = User::factory()->create();
    $session = login($user);

    withSession($session);
    getJson('/api/me')->assertOk();

    $token = session()->token();

    withSession($session);
    postJson('/api/logout')->assertNoContent();

    expect(session()->token())->not->toBe($token);

    withSession($session);
    getJson('/api/me')->assertUnauthorized();
});
