<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Package;
use App\Models\Repository;
use App\Models\Token;
use Illuminate\Database\Eloquent\Collection;

interface Tokenable
{
    public function currentAccessToken(): Token;

    public function hasAccessToRepository(Repository $repository): bool;

    /**
     * Check if the token has access to a specific package.
     *
     * Access is granted if either:
     * 1. Token has access to the package's repository (repository-level access), OR
     * 2. Token has direct access to the specific package (package-level access)
     */
    public function hasAccessToPackage(Package $package): bool;

    /**
     * Filter a collection of packages to only those the token has access to.
     *
     * This method performs batch access checking with optimized queries
     * instead of N+1 queries when checking each package individually.
     *
     * @param  Collection<int, Package>|array<Package>  $packages
     * @return Collection<int, Package>
     */
    public function filterAccessiblePackages(Collection|array $packages): Collection;

    /**
     * @phpstan-ignore-next-line
     */
    public function tokenCan(string $ability);
}
