<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Contracts\Tokenable;
use App\Models\Package;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Builder;

readonly class PackageTokenAccessScope
{
    public function __construct(
        private ?Tokenable $token,
        private Repository $repository,
    ) {
        //
    }

    /**
     * @param  Builder<Package>  $query
     * @return Builder<Package>
     */
    public function apply(Builder $query): Builder
    {
        $query->where('repository_id', $this->repository->id);

        if ($this->repository->public || $this->token === null || $this->token->isUnscoped()) {
            return $query;
        }

        return $query->where(function (Builder $scopedQuery): void {
            $scopedQuery
                ->whereIn('repository_id', $this->token->accessibleRepositoryIdsQuery())
                ->orWhereIn('id', $this->token->accessiblePackageIdsQuery());
        });
    }
}
