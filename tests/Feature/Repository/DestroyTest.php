<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\RepositoryResource;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\deleteJson;

it('destroys', function (?User $user, int $status): void {
    $repository = Repository::factory()->create();

    $response = deleteJson("/repositories/$repository->id")
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(new RepositoryResource(
            $repository
        ))
    );

    assertModelMissing($repository);
})
    ->with(guestAndUsers([Permission::REPOSITORY_DELETE, Permission::UNSCOPED]));
