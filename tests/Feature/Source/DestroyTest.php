<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use App\Models\User;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\deleteJson;

it('destroys', function (?User $user, int $status): void {
    $source = Source::factory()->create();

    $response = deleteJson("/api/sources/$source->id")
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(
            new SourceResource($source),
        )
    );

    assertModelMissing($source);
})->with(guestAndUsers(Permission::SOURCE_DELETE));
