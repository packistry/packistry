<?php

declare(strict_types=1);

use App\Models\DeployToken;
use App\Models\Package;
use App\Models\Repository;

it('filters packages by repository access for deploy tokens', function (): void {
    $repository = Repository::factory()->withPackages(count: 3)->create();
    $otherRepository = Repository::factory()->withPackages(count: 2)->create();

    /** @var DeployToken $token */
    $token = DeployToken::factory()->create();
    $token->repositories()->sync([$repository->id]);

    $allPackages = Package::all();
    $accessiblePackages = $token->filterAccessiblePackages($allPackages);

    expect($accessiblePackages)->toHaveCount(3);
    expect($accessiblePackages->pluck('id')->all())
        ->toEqual($repository->packages->pluck('id')->all());
});

it('filters packages by direct package access for deploy tokens', function (): void {
    $repository = Repository::factory()->withPackages(count: 3)->create();

    /** @var DeployToken $token */
    $token = DeployToken::factory()->create();

    // Grant access to only the first package
    /** @var Package $firstPackage */
    $firstPackage = $repository->packages->first();
    $token->packages()->sync([$firstPackage->id]);

    $accessiblePackages = $token->filterAccessiblePackages($repository->packages);

    expect($accessiblePackages)->toHaveCount(1);

    /** @var Package $accessibleFirst */
    $accessibleFirst = $accessiblePackages->first();
    expect($accessibleFirst->id)->toBe($firstPackage->id);
});

it('combines repository and direct package access for deploy tokens', function (): void {
    $repository1 = Repository::factory()->withPackages(count: 2)->create();
    $repository2 = Repository::factory()->withPackages(count: 3)->create();

    /** @var DeployToken $token */
    $token = DeployToken::factory()->create();

    // Grant access to repository1 (all packages)
    $token->repositories()->sync([$repository1->id]);

    // Grant direct access to first package of repository2
    /** @var Package $firstPackageRepo2 */
    $firstPackageRepo2 = $repository2->packages->first();
    $token->packages()->sync([$firstPackageRepo2->id]);

    $allPackages = Package::all();
    $accessiblePackages = $token->filterAccessiblePackages($allPackages);

    // Should have 2 packages from repository1 + 1 from repository2
    expect($accessiblePackages)->toHaveCount(3);
});

it('returns empty collection when no packages are accessible for deploy tokens', function (): void {
    $repository = Repository::factory()->withPackages(count: 3)->create();

    /** @var DeployToken $token */
    $token = DeployToken::factory()->create();

    $accessiblePackages = $token->filterAccessiblePackages($repository->packages);

    expect($accessiblePackages)->toHaveCount(0);
});

it('handles empty input for deploy tokens', function (): void {
    /** @var DeployToken $token */
    $token = DeployToken::factory()->create();

    $accessiblePackages = $token->filterAccessiblePackages([]);

    expect($accessiblePackages)->toHaveCount(0);
});

it('accepts array input for deploy tokens', function (): void {
    $repository = Repository::factory()->withPackages(count: 3)->create();

    /** @var DeployToken $token */
    $token = DeployToken::factory()->create();
    $token->repositories()->sync([$repository->id]);

    $packagesArray = $repository->packages->all();
    $accessiblePackages = $token->filterAccessiblePackages($packagesArray);

    expect($accessiblePackages)->toHaveCount(3);
});
