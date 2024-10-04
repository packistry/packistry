<?php

declare(strict_types=1);

namespace App\Actions\PersonalTokens;

use App\Actions\PersonalTokens\Inputs\StorePersonalTokenInput;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class StorePersonalToken
{
    public function handle(User $user, StorePersonalTokenInput $input): NewAccessToken
    {
        return $user->createToken(
            name: $input->name,
            abilities: $input->abilities,
            expiresAt: $input->expiresAt
        );
    }
}
