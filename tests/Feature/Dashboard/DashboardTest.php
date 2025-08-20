<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\User;

use function Pest\Laravel\getJson;

it('shows dashboard', function (?User $user, int $status): void {
    $response = getJson('/api/dashboard')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertJsonStructure([
        'packages',
        'repositories',
        'users',
        'tokens',
        'sources',
        'downloads',
    ]);

})->with(guestAndUsers([
    Permission::DASHBOARD,
    Permission::REPOSITORY_READ,
    Permission::PACKAGE_READ,
    Permission::USER_READ,
    Permission::SOURCE_READ,
    Permission::DEPLOY_TOKEN_READ,
]));
