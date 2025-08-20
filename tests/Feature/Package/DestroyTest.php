<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\deleteJson;

it('destroys', function (?User $user, int $status): void {
    /** @var Package $package */
    $package = Package::factory()
        ->for(Repository::factory())
        ->create();

    $response = deleteJson("/api/packages/$package->id")
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    // @todo check if archives are cleaned
    $response->assertExactJson(resourceAsJson(new PackageResource($package)));
})->with([
    ...guestAndUsers(Permission::PACKAGE_DELETE, userWithPermission: 404),
    ...unscopedUser(Permission::PACKAGE_DELETE),
]);
