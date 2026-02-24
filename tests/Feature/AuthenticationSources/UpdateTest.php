<?php

declare(strict_types=1);

use App\Enums\AuthenticationProvider;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Resources\AuthenticationSourceResource;
use App\Models\AuthenticationSource;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use App\OIDCConfiguration;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\patchJson;

it('updates', function (?User $user, int $status): void {
    $source = AuthenticationSource::factory()->create();
    $repositories = Repository::factory()
        ->count(10)
        ->create();
    $packages = Package::factory()
        ->for($repositories->first())
        ->count(3)
        ->create();

    $discoveryUrl = 'https://company.okta.com/.well-known/openid-configuration';

    Http::fake([
        $discoveryUrl => Http::response((new OIDCConfiguration(
            userinfoEndpoint: '',
            tokenEndpoint: '',
            authorizationEndpoint: '',
        ))->toArray()),
    ]);

    $response = patchJson("/api/authentication-sources/$source->id", $attributes = [
        'name' => fake()->name,
        'provider' => AuthenticationProvider::OIDC,
        'client_id' => Str::random(),
        'client_secret' => Str::random(),
        'discovery_url' => $discoveryUrl,
        'icon_url' => fake()->url,
        'active' => fake()->boolean,
        'default_user_role' => fake()->randomElement(Role::cases()),
        'default_user_repositories' => $repositories->pluck('id'),
        'default_user_packages' => $packages->pluck('id'),
    ])
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(new AuthenticationSourceResource(
            $source = AuthenticationSource::query()->first()
        ))
    );

    expect($source)
        ->toBeInstanceOf(AuthenticationSource::class)
        ->name->toBe($attributes['name'])
        ->provider->toBe($attributes['provider'])
        ->icon_url->toBe($attributes['icon_url'])
        ->active->toBe($attributes['active'])
        ->client_id->toBe($attributes['client_id'])
        ->discovery_url->toBe($attributes['discovery_url'])
        ->and($source->repositories->pluck('id'))->toEqual($repositories->pluck('id'))
        ->and($source->packages->pluck('id'))->toEqual($packages->pluck('id'));
})
    ->with(guestAndUsers(Permission::AUTHENTICATION_SOURCE_UPDATE));
