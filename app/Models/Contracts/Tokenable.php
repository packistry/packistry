<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Package;
use App\Models\Repository;
use App\Models\Token;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder;

interface Tokenable
{
    public function currentAccessToken(): Token;

    public function accessibleRepositoryIdsQuery(): Builder;

    public function accessiblePackageIdsQuery(): Builder;

    public function isUnscoped(): bool;

    /**
     * @return BelongsToMany<Repository, covariant Model>
     */
    public function repositories(): BelongsToMany;

    /**
     * @return BelongsToMany<Package, covariant Model>
     */
    public function packages(): BelongsToMany;

    /**
     * @phpstan-ignore-next-line
     */
    public function tokenCan(string $ability);
}
