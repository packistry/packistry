<?php

use App\Enums\Permission;
use App\Models\User;

use function Pest\Laravel\getJson;

it('indexes', function (?User $user, int $status): void {
    getJson('/batches')
        ->assertStatus($status);
})->with(guestAndUsers(Permission::BATCH_READ));
