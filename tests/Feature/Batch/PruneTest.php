<?php

use App\Enums\Permission;
use App\Models\User;

use function Pest\Laravel\deleteJson;

it('indexes', function (?User $user, int $status): void {
    deleteJson('/api/batches')
        ->assertStatus($status);
})->with(guestAndUsers(Permission::BATCH_DELETE));
