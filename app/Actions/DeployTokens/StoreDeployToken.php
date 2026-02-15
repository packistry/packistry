<?php

declare(strict_types=1);

namespace App\Actions\DeployTokens;

use App\Actions\DeployTokens\Inputs\StoreDeployTokenInput;
use App\Models\DeployToken;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\NewAccessToken;
use Throwable;

class StoreDeployToken
{
    /**
     * @return array{DeployToken, NewAccessToken}
     *
     * @throws Throwable
     */
    public function handle(StoreDeployTokenInput $input): array
    {
        return DB::transaction(function () use ($input): array {
            $token = new DeployToken;

            $token->name = $input->name;

            $token->save();

            if (is_array($input->repositories)) {
                $token->repositories()->sync($input->repositories);
            }

            if (is_array($input->packages)) {
                $token->packages()->sync($input->packages);
            }

            $accessToken = $token->createToken(
                name: $token->name,
                abilities: $input->abilities,
                expiresAt: $input->expiresAt
            );

            return [$token, $accessToken];
        });
    }
}
