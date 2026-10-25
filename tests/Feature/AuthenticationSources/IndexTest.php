<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\AuthenticationSourceResource;
use App\Models\AuthenticationSource;
use App\Models\User;

use function Pest\Laravel\getJson;

it('indexes', function (?User $user, int $status): void {
    $sources = AuthenticationSource::factory()->count(10)->create();

    $sources->load('repositories', 'packages');

    $response = getJson('/api/authentication-sources')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertJsonPath('data', resourceAsJson(AuthenticationSourceResource::collection($sources)));
})->with(guestAndUsers(Permission::AUTHENTICATION_SOURCE_READ));
