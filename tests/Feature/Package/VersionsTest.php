<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\VersionResource;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use App\Models\Version;

use function Pest\Laravel\getJson;

it('shows package versions', function (?User $user, int $status): void {
    Package::factory()
        ->for(Repository::factory())
        ->has(Version::factory()->count(5))
        ->create();

    $versions = Version::query()
        ->paginate(10);

    $response = getJson('/packages/1/versions')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertJsonPath('data', json_decode(VersionResource::collection($versions)->toJson(), true));
})
    ->with([
        ...guestAndUsers(Permission::PACKAGE_READ, userWithPermission: 404),
        ...unscopedUser(Permission::PACKAGE_READ),
    ]);
