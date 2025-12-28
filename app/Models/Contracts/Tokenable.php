<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Package;
use App\Models\Repository;
use App\Models\Token;

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
     * @phpstan-ignore-next-line
     */
    public function tokenCan(string $ability);
}
