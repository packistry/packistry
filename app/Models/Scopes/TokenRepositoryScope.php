<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Builders\RepositoryBuilder;
use App\Models\Contracts\Tokenable;

readonly class TokenRepositoryScope
{
    public function __construct(
        private ?Tokenable $token,
    ) {
        //
    }

    public function apply(RepositoryBuilder $query): RepositoryBuilder
    {
        if ($this->token === null) {
            return $query->public();
        }

        if ($this->token->isUnscoped()) {
            return $query;
        }

        return $query->whereIn('id', $this->token->accessibleRepositoryIdsQuery());
    }
}
