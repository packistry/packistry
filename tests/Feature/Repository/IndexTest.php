<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\RepositoryResource;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotNull;

it('shows index', function (?User $user, int $status): void {
    Repository::factory()
        ->count(10)
        ->create();

    $query = $user instanceof User
        ? Repository::userScoped($user)
        : Repository::query();

    if ($user instanceof User) {
        $user->repositories()->sync([
            1, 2, 3, 4, 5,
        ]);
    }

    $repositories = $query
        ->withCount('packages')
        ->paginate(10);

    $response = getJson('/api/repositories')
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    assertNotNull($user);

    $response->assertJsonPath('data', json_decode(RepositoryResource::collection($repositories)->toJson(), true));
    $response->assertJsonCount($user->can(Permission::UNSCOPED) ? 10 : 5, 'data');
})
    ->with([
        ...guestAndUsers(Permission::REPOSITORY_READ),
        ...unscopedUser(Permission::REPOSITORY_READ),
    ]);

it('searches', function (?User $user, int $status): void {
    $repository = Repository::factory()
        ->state([
            'name' => $name = fake()->name,
            'description' => $description = fake()->text,
        ])
        ->create();

    $repository->loadCount('packages');

    getJson("/api/repositories?filter[search]=$name")
        ->assertStatus($status)
        ->assertJsonPath('data', resourceAsJson(RepositoryResource::collection([$repository])));

    getJson("/api/repositories?filter[search]=$description")
        ->assertStatus($status)
        ->assertJsonPath('data', resourceAsJson(RepositoryResource::collection([$repository])));

    getJson('/api/repositories?filter[search]=something%20else')
        ->assertStatus($status)
        ->assertJsonPath('data', []);
})
    ->with(unscopedUser(Permission::REPOSITORY_READ));

it('filters by public', function (?User $user, int $status): void {
    $repository = Repository::factory()
        ->state([
            'public' => true,
        ])
        ->create();

    $repository->loadCount('packages');

    getJson('/api/repositories?filter[public]=true')
        ->assertStatus($status)
        ->assertJsonPath('data', resourceAsJson(RepositoryResource::collection([$repository])));

    getJson('/api/repositories?filter[public]=false')
        ->assertStatus($status)
        ->assertJsonPath('data', []);
})
    ->with(unscopedUser(Permission::REPOSITORY_READ));
