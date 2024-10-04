<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Repository;
use App\Models\Token;

interface Tokenable
{
    public function currentAccessToken(): Token;

    public function hasAccessToRepository(Repository $repository): bool;

    /**
     * @phpstan-ignore-next-line
     */
    public function tokenCan(string $ability);
}
