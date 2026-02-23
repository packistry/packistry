<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Builders\PackageBuilder;
use App\Models\Contracts\Tokenable;
use App\Models\Repository;
use Illuminate\Support\Facades\Auth;

readonly class TokenPackageScope
{
    public function __construct(
        private ?Tokenable $token,
    ) {
        //
    }

    public function apply(PackageBuilder $query): PackageBuilder
    {
        $token = $this->token ?? Auth::guard('sanctum')->user();

        if ($token === null) {
            return $query->whereIn('repository_id', Repository::query()->public()->select('id'));
        }

        if ($token->isUnscoped()) {
            return $query;
        }

        return $query->whereIn('id', $token->accessiblePackageIdsQuery());
    }
}
