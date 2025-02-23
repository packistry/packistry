<?php

declare(strict_types=1);

use App\Models\AuthenticationSource;
use App\Models\User;

use function Pest\Laravel\postJson;

it('can authenticate with correct credentials', function () {
    $user = User::factory()
        ->create();

    postJson('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk();

    expect(auth()->check())->toBeTrue();
});

it('can not authenticate with incorrect credentials', function () {
    $user = User::factory()
        ->create();

    postJson('/login', ['email' => $user->email, 'password' => 'incorrect'])
        ->assertUnprocessable()
        ->assertExactJson(validation([
            'email' => ['The provided credentials are incorrect.'],
        ]));

    expect(auth()->check())->toBeFalse();
});

it('can not use local authentication with authentication source', function () {
    $user = User::factory()
        ->for(AuthenticationSource::factory())
        ->create();

    postJson('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertUnprocessable()
        ->assertExactJson(validation([
            'email' => ['The provided credentials are incorrect.'],
        ]));

    expect(auth()->check())->toBeFalse();
});
