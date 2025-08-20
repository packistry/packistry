<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use App\Models\User;

use function Pest\Laravel\getJson;

it('indexes', function (?User $user, int $status): void {
    $sources = Source::factory()->count(10)->create();

    $response = getJson('/api/sources')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(SourceResource::collection($sources))
    );
})->with(guestAndUsers(Permission::SOURCE_READ));
