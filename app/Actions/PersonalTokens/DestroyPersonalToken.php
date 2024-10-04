<?php

declare(strict_types=1);

namespace App\Actions\PersonalTokens;

use App\Models\Token;

class DestroyPersonalToken
{
    public function handle(Token $token): Token
    {
        $token->delete();

        return $token;
    }
}
