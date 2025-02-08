<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Source;
use App\Models\User;
use App\Models\Version;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use function Pest\Laravel\getJson;

it('shows package', function (?User $user, int $status): void {
    $package = Package::factory()
        ->for(Source::factory())
        ->for(Repository::factory())
        ->has(Version::factory()->count(2))
        ->create();

    $response = getJson("/packages/$package->id")
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(new PackageResource($package->fresh()->load([
            'versions' => fn (HasMany $query) => $query->withCount('downloads'),
            'repository' => fn (BelongsTo $query) => $query->withCount('packages'),
            'source',
        ])))
    );
})
    ->with([
        ...guestAndUsers(Permission::PACKAGE_READ, userWithPermission: 404),
        ...unscopedUser(Permission::PACKAGE_READ),
    ]);
