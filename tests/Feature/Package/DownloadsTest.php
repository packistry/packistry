<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use App\Models\Version;

use function Pest\Laravel\getJson;

it('shows package downloads', function (?User $user, int $status): void {
    Package::factory()
        ->for(Repository::factory())
        ->has(Version::factory()->count(5))
        ->create();

    $response = getJson('/api/packages/1/downloads')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertJsonCount(90);
})
    ->with([
        ...guestAndUsers(Permission::PACKAGE_READ, userWithPermission: 404),
        ...unscopedUser(Permission::PACKAGE_READ),
    ]);
