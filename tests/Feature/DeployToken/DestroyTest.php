<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\DeployToken;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\deleteJson;

it('destroys', function (?User $user, int $status): void {
    Repository::factory()->create();

    /** @var DeployToken $token */
    $token = DeployToken::factory()->create();

    deleteJson("/api/deploy-tokens/$token->id")
        ->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    assertModelMissing($token);
    assertSoftDeleted($token->token);
})
    ->with(guestAndUsers(Permission::DEPLOY_TOKEN_DELETE));
