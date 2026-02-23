<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Contracts\Tokenable;
use App\Models\Package;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Builder;

readonly class TokenPackageScope
{
    public function __construct(
        private ?Tokenable $token,
    ) {
        //
    }

    /**
     * @param  Builder<Package>  $query
     * @return Builder<Package>
     */
    public function apply(Builder $query): Builder
    {
        if ($this->token === null) {
            return $query->whereIn('repository_id', Repository::query()->public()->select('id'));
        }

        if ($this->token->isUnscoped()) {
            return $query;
        }

        return $query->whereIn('id', $this->token->accessiblePackageIdsQuery());
    }
}
