<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;

it('filters packages by repository access for users', function (): void {
    $repository = Repository::factory()->withPackages(count: 3)->create();
    $otherRepository = Repository::factory()->withPackages(count: 2)->create();

    /** @var User $user */
    $user = User::factory()->create();
    $user->repositories()->sync([$repository->id]);

    $allPackages = Package::all();
    $accessiblePackages = $user->filterAccessiblePackages($allPackages);

    expect($accessiblePackages)->toHaveCount(3);
    expect($accessiblePackages->pluck('id')->all())
        ->toEqual($repository->packages->pluck('id')->all());
});

it('returns all packages for unscoped users', function (): void {
    $repository = Repository::factory()->withPackages(count: 3)->create();
    $otherRepository = Repository::factory()->withPackages(count: 2)->create();

    /** @var User $user */
    $user = User::factory()->create();
    config()->set("authorization.{$user->role->value}", [Permission::UNSCOPED]);

    $allPackages = Package::all();
    $accessiblePackages = $user->filterAccessiblePackages($allPackages);

    expect($accessiblePackages)->toHaveCount(5);
});

it('returns empty collection when user has no repository access', function (): void {
    $repository = Repository::factory()->withPackages(count: 3)->create();

    /** @var User $user */
    $user = User::factory()->create();

    $accessiblePackages = $user->filterAccessiblePackages($repository->packages);

    expect($accessiblePackages)->toHaveCount(0);
});

it('handles empty input for users', function (): void {
    /** @var User $user */
    $user = User::factory()->create();

    $accessiblePackages = $user->filterAccessiblePackages([]);

    expect($accessiblePackages)->toHaveCount(0);
});
