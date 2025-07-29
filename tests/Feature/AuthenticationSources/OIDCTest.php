<?php

declare(strict_types=1);

use App\Actions\AuthenticationSources\OAUTH_ERRORS;
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
        'allow_registration' => true,
        'allowed_domains' => ['example.com'],
    ]);

    $this->source_oidc_deny_registration = AuthenticationSource::factory()->create([
        'provider' => AuthenticationProvider::OIDC,
        'allow_registration' => false,
        'allowed_domains' => ['example.com'],
    ]);

    $baseUrl = parse_url($this->source->discovery_url)['host'];
    $baseUrlOIDCDenyRegistration = parse_url($this->source_oidc_deny_registration->discovery_url)['host'];

    $this->config = new OIDCConfiguration(
        userinfoEndpoint: "$baseUrl/oauth2/v1/userinfo",
        tokenEndpoint: "$baseUrl/oauth2/v1/token",
        authorizationEndpoint: "$baseUrl/oauth2/v1/authorize",
    );

    $this->config_oidc_deny_registration = new OIDCConfiguration(
        userinfoEndpoint: "$baseUrlOIDCDenyRegistration/oauth2/v1/userinfo",
        tokenEndpoint: "$baseUrlOIDCDenyRegistration/oauth2/v1/token",
        authorizationEndpoint: "$baseUrlOIDCDenyRegistration/oauth2/v1/authorize",
    );

    Http::preventStrayRequests();
    Http::fake([
        $this->source->discovery_url => Http::response($this->config->toArray()),
        $this->source_oidc_deny_registration->discovery_url => Http::response($this->config_oidc_deny_registration->toArray()),
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

it('handles OIDC callback and deny user creation', function () {
    session()->put('state', $state = Str::random(40));

    Http::fake([
        $this->config_oidc_deny_registration->userinfoEndpoint => Http::response([
            'sub' => '123456',
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
        ]),
        $this->config_oidc_deny_registration->tokenEndpoint => Http::response([
            'access_token' => Str::random(),
        ]),
    ]);

    $msg = rawurlencode(OAUTH_ERRORS::REGISTRATION_NOT_ALLOWED->value);

    get("{$this->source_oidc_deny_registration->callbackUrl()}?state=$state")
        ->assertRedirect("/login?oauth_error=$msg");

    expect(Auth::check())->toBeFalse();
});

it('handles OIDC callback and allow user creation but domain mismatch', function () {
    session()->put('state', $state = Str::random(40));

    Http::fake([
        $this->config->userinfoEndpoint => Http::response([
            'sub' => '123456',
            'name' => 'John Doe',
            'email' => 'johndoe@example.net',
        ]),
        $this->config->tokenEndpoint => Http::response([
            'access_token' => Str::random(),
        ]),
    ]);

    $msg = rawurlencode(OAUTH_ERRORS::INVALID_DOMAIN->value);

    get("{$this->source->callbackUrl()}?state=$state")
        ->assertRedirect("/login?oauth_error=$msg");

    expect(Auth::check())->toBeFalse();
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

it('updates existing user on OIDC login with deny of user registration logic', function () {
    session()->put('state', $state = Str::random(40));

    Http::fake([
        $this->config_oidc_deny_registration->userinfoEndpoint => Http::response([
            'sub' => '123456',
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
        ]),
        $this->config_oidc_deny_registration->tokenEndpoint => Http::response([
            'access_token' => Str::random(),
        ]),
    ]);

    User::factory()
        ->for($this->source_oidc_deny_registration)
        ->create([
            'role' => Role::ADMIN,
            'email' => 'johndoe@example.com',
            'name' => 'Old Name',
            'external_id' => '123456',
        ]);

    $this->source_oidc_deny_registration->repositories()
        ->sync(Repository::factory()->count(10)->create());

    get("{$this->source_oidc_deny_registration->callbackUrl()}?state=$state")
        ->assertRedirect('/');

    /** @var User $user */
    $user = auth()->user();

    expect($user)
        ->name->toBe('John Doe')
        ->email->toBe('johndoe@example.com')
        ->authentication_source_id->toBe($this->source_oidc_deny_registration->id)
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

    $msg = rawurlencode('HTTP request returned status code 503');

    get("{$this->source->callbackUrl()}?state=$state")
        ->assertRedirect("/login?oauth_error=$msg");

    expect(Auth::check())->toBeFalse();
});
