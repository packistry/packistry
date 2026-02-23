<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Token;
use Illuminate\Database\Query\Builder;

interface Tokenable
{
    public function currentAccessToken(): Token;

    public function accessibleRepositoryIdsQuery(): Builder;

    public function accessiblePackageIdsQuery(): Builder;

    public function isUnscoped(): bool;

    /**
     * @phpstan-ignore-next-line
     */
    public function tokenCan(string $ability);
}
