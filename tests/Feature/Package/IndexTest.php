<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotNull;

it('shows index', function (?User $user, int $status): void {
    Package::factory()
        ->for(Repository::factory())
        ->count(10)
        ->create();

    $query = $user instanceof User
        ? Package::userScoped($user)
        : Package::query();

    $packages = $query
        ->paginate(10);

    $response = getJson('/api/packages')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    assertNotNull($user);

    $response->assertJsonPath('data', json_decode(PackageResource::collection($packages)->toJson(), true));
    $response->assertJsonCount($user->can(Permission::UNSCOPED) ? 10 : 0, 'data');
})
    ->with([
        ...guestAndUsers(Permission::PACKAGE_READ),
        ...unscopedUser(Permission::PACKAGE_READ),
    ]);

it('searches', function (?User $user, int $status): void {
    $package = Package::factory()
        ->state([
            'name' => $name = fake()->name,
            'description' => $description = fake()->text,
        ])
        ->for(Repository::factory())
        ->create();

    getJson("/api/packages?filter[search]=$name")
        ->assertStatus($status)
        ->assertJsonPath('data', resourceAsJson(PackageResource::collection([$package])));

    getJson("/api/packages?filter[search]=$description")
        ->assertStatus($status)
        ->assertJsonPath('data', resourceAsJson(PackageResource::collection([$package])));

    getJson('/api/packages?filter[search]=something%20else')
        ->assertStatus($status)
        ->assertJsonPath('data', []);
})
    ->with(unscopedUser(Permission::PACKAGE_READ));

it('filters by repository id', function (?User $user, int $status): void {
    $package = Package::factory()
        ->for(Repository::factory())
        ->create();

    getJson('/api/packages?filter[repository_id]=1')
        ->assertStatus($status)
        ->assertJsonPath('data', resourceAsJson(PackageResource::collection([$package])));

    getJson('/api/packages?filter[repository_id]=2')
        ->assertStatus($status)
        ->assertJsonPath('data', []);
})
    ->with(unscopedUser(Permission::PACKAGE_READ));

it('shows package with package-level user access', function (): void {
    $user = user(Permission::PACKAGE_READ);
    $repository = Repository::factory()->create();
    $package = Package::factory()->for($repository)->create();

    $user->packages()->sync([$package->id]);

    $expected = Package::userScoped($user)
        ->paginate(10);

    getJson('/api/packages')
        ->assertOk()
        ->assertJsonPath('data', json_decode(PackageResource::collection($expected)->toJson(), true))
        ->assertJsonCount(1, 'data');
});
