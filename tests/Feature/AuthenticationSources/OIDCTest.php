<?php

declare(strict_types=1);

use App\Enums\AuthenticationProvider;
use App\Enums\Role;
use App\Models\AuthenticationSource;
use App\Models\Repository;
use App\Models\User;
use App\OIDCConfiguration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->source = AuthenticationSource::factory()->create([
        'provider' => AuthenticationProvider::OIDC,
    ]);

    $baseUrl = parse_url($this->source->discovery_url)['host'];

    $this->config = new OIDCConfiguration(
        userinfoEndpoint: "$baseUrl/oauth2/v1/userinfo",
        tokenEndpoint: "$baseUrl/oauth2/v1/token",
        authorizationEndpoint: "$baseUrl/oauth2/v1/authorize",
    );

    Http::preventStrayRequests();
    Http::fake([
        $this->source->discovery_url => Http::response($this->config->toArray()),
    ]);
});

it('redirects to OIDC provider', function () {
    $response = get($this->source->redirectUrl());

    $response->assertRedirect();

    expect($response->headers->get('Location'))
        ->toContain('authorize')
        ->toContain('client_id')
        ->toContain('scope')
        ->toContain('redirect_uri')
        ->toContain('state')
        ->toContain('response_type');
});

it('handles OIDC callback and creates user', function () {
    session()->put('state', $state = Str::random(40));

    Http::fake([
        $this->config->userinfoEndpoint => Http::response([
            'sub' => '123456',
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
        ]),
        $this->config->tokenEndpoint => Http::response([
            'access_token' => Str::random(),
        ]),
    ]);

    $this->source->repositories()
        ->sync($repositories = Repository::factory()->count(10)->create());

    get("{$this->source->callbackUrl()}?state=$state")
        ->assertRedirect('/');

    /** @var User $user */
    $user = auth()->user();

    expect($user)
        ->name->toBe('John Doe')
        ->email->toBe('johndoe@example.com')
        ->role->toBe(Role::USER)
        ->authentication_source_id->toBe(1)
        ->external_id->toBe('123456')
        ->and($user->repositories->pluck('id')->toArray())->toEqual($repositories->pluck('id')->toArray())
        ->and(Auth::check())->toBeTrue();
});

it('updates existing user on OIDC login', function () {
    session()->put('state', $state = Str::random(40));

    Http::fake([
        $this->config->userinfoEndpoint => Http::response([
            'sub' => '123456',
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
        ]),
        $this->config->tokenEndpoint => Http::response([
            'access_token' => Str::random(),
        ]),
    ]);

    User::factory()
        ->for($this->source)
        ->create([
            'role' => Role::ADMIN,
            'email' => 'johndoe@example.com',
            'name' => 'Old Name',
            'external_id' => '123456',
        ]);

    $this->source->repositories()
        ->sync(Repository::factory()->count(10)->create());

    get("{$this->source->callbackUrl()}?state=$state")
        ->assertRedirect('/');

    /** @var User $user */
    $user = auth()->user();

    expect($user)
        ->name->toBe('John Doe')
        ->email->toBe('johndoe@example.com')
        ->authentication_source_id->toBe($this->source->id)
        ->external_id->toBe('123456')
        ->role->toBe(Role::ADMIN)
        ->and($user->repositories->toArray())->toBe([])
        ->and(Auth::check())->toBeTrue();
});

it('fails login if OIDC provider returns an error', function () {
    Http::fake([
        '*' => Http::response(status: 503),
    ]);

    session()->put('state', $state = Str::random(40));

    get("{$this->source->callbackUrl()}?state=$state")
        ->assertRedirect('/login');

    expect(Auth::check())->toBeFalse();
});
