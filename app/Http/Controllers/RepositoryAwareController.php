<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\TokenAbility;
use App\Models\Contracts\Tokenable;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

abstract class RepositoryAwareController
{
    protected function token(): ?Tokenable
    {
        /** @var Tokenable $token */
        $token = Auth::guard('sanctum')->user();

        return $token;
    }

    protected function repository(): Repository
    {
        return once(function () {
            $path = request()->route('repository');

            if (is_object($path)) {
                abort(401);
            }

            return Repository::queryByPath($path)
                ->firstOrFail();
        });
    }

    protected function authorize(TokenAbility $ability): void
    {
        $token = $this->token();
        $repository = $this->repository();

        if (in_array($ability, TokenAbility::readAbilities()) && $repository->public) {
            return;
        }

        if (is_null($token) || ! $token->tokenCan($ability->value)) {
            abort(401);
        }

        if ($token instanceof User && $token->can(Permission::UNSCOPED)) {
            return;
        }

        if (! $token->hasAccessToRepository($repository)) {
            abort(401);
        }
    }
}
