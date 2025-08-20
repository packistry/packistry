<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\AuthenticationSourceResource;
use App\Models\AuthenticationSource;
use App\Models\User;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\deleteJson;

it('destroys', function (?User $user, int $status): void {
    $source = AuthenticationSource::factory()
        ->create();

    $response = deleteJson("/api/authentication-sources/$source->id")
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(
            new AuthenticationSourceResource($source),
        )
    );

    assertModelMissing($source);
})->with(guestAndUsers(Permission::AUTHENTICATION_SOURCE_DELETE));
