<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\DeployToken;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\deleteJson;
use function PHPUnit\Framework\assertNotNull;

it('destroys', function (?User $user, int $status): void {
    Repository::factory()->create();

    /** @var DeployToken $token */
    $token = DeployToken::factory()->create();

    deleteJson("/deploy-tokens/$token->id")
        ->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    assertModelMissing($token);
    assertNotNull($token->token);
    assertModelMissing($token->token);
})
    ->with(guestAndUsers(Permission::DEPLOY_TOKEN_DELETE));
