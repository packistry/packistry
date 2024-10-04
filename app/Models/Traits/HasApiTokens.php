<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Contracts\Tokenable;
use App\Models\Token;
use DateTimeInterface;
use Laravel\Sanctum\NewAccessToken;

/**
 * @phpstan-require-implements Tokenable
 */
trait HasApiTokens
{
    use \Laravel\Sanctum\HasApiTokens;

    /**
     * @param  string[]  $abilities
     */
    public function createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): NewAccessToken
    {
        $plainTextToken = $this->generateTokenString();

        /** @var Token $token */
        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return new NewAccessToken($token, "{$token->type()->prefix()}-$plainTextToken");
    }

    public function currentAccessToken(): Token
    {
        /** @var Token $token */
        $token = $this->accessToken;

        return $token;
    }
}
