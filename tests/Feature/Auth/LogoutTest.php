<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\postJson;

it('can logout', function () {
    $user = User::factory()
        ->create();

    postJson('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk();

    expect(auth()->guard('web')->check())->toBeTrue();

    postJson('/logout')
        ->assertNoContent();

    expect(auth()->guard('web')->check())->toBeFalse();
});
