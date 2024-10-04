<?php

declare(strict_types=1);

namespace App\Actions\DeployTokens;

use App\Models\DeployToken;
use Illuminate\Support\Facades\DB;
use Throwable;

class DestroyDeployToken
{
    /**
     * @throws Throwable
     */
    public function handle(DeployToken $token): DeployToken
    {
        return DB::transaction(function () use ($token): DeployToken {
            $token->token()->delete();
            $token->delete();

            return $token;
        });
    }
}
