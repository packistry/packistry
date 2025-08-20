<?php

declare(strict_types=1);

use App\Http\Resources\PublicAuthenticationSourceResource;
use App\Models\AuthenticationSource;

use function Pest\Laravel\getJson;

it('shows public index', function (): void {
    $sources = AuthenticationSource::factory()
        ->count(5)
        ->create();

    getJson('/api/auths')
        ->assertStatus(200)
        ->assertExactJson(
            resourceAsJson(PublicAuthenticationSourceResource::collection($sources))
        );
});
