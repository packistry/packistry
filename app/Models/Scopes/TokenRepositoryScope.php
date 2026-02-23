<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Builders\RepositoryBuilder;
use App\Models\Contracts\Tokenable;
use Illuminate\Support\Facades\Auth;

readonly class TokenRepositoryScope
{
    public function __construct(
        private ?Tokenable $token,
    ) {
        //
    }

    public function apply(RepositoryBuilder $query): RepositoryBuilder
    {
        $token = $this->token ?? Auth::guard('sanctum')->user();
        
        if ($token === null) {
            return $query->public();
        }

        if ($token->isUnscoped()) {
            return $query;
        }

        return $query->whereIn('id', $token->accessibleRepositoryIdsQuery());
    }
}
