<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Builders\RepositoryBuilder;
use App\Models\Contracts\Tokenable;
use Illuminate\Database\Eloquent\Builder;

readonly class TokenRepositoryScope
{
    public function __construct(
        private ?Tokenable $token,
    ) {
        //
    }

    /**
     * @param  RepositoryBuilder  $query
     */
    public function apply(Builder $query): RepositoryBuilder
    {
        if ($this->token === null) {
            $query->public();
        }

        if ($this->token->isUnscoped()) {
            return $query;
        }

        return $query->whereIn('id', $this->token->accessibleRepositoryIdsQuery());
    }
}
