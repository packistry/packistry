<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TokenAbility;
use App\Models\Contracts\Tokenable;
use App\Models\Repository;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

abstract class RepositoryAwareController
{
    protected function token(): ?Tokenable
    {
        $token = Auth::guard('sanctum')->user();

        if ($token !== null && ! $token instanceof Tokenable) {
            return throw new RuntimeException('Authenticatable class must implement '.Tokenable::class);
        }

        return $token;
    }

    protected function repository(): Repository
    {
        return once(function () {
            $path = request()->route('repository');

            if (is_object($path)) {
                abort(401);
            }

            return Repository::query()
                ->queryByPath($path)
                ->tokenScoped($this->token())
                ->firstOrFail();
        });
    }

    protected function authorize(TokenAbility $ability): void
    {
        $token = $this->token();
        $repository = $this->repository();

        if (in_array($ability, TokenAbility::readAbilities(), true) && $repository->public) {
            return;
        }

        if (is_null($token) || ! $token->tokenCan($ability->value) || $token->currentAccessToken()->isExpired()) {
            abort(401);
        }
    }
}
