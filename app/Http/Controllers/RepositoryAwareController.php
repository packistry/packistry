<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\TokenAbility;
use App\Models\Contracts\Tokenable;
use App\Models\DeployToken;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

abstract class RepositoryAwareController
{
    protected function token(): ?Tokenable
    {
        $token = Auth::guard('sanctum')->user();

        if ($token !== null && ! $token instanceof Tokenable) {
            return throw new \RuntimeException('Authenticatable class must implement '.Tokenable::class);
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

            return Repository::queryByPath($path)
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

        if ($token instanceof User && $token->can(Permission::UNSCOPED)) {
            return;
        }

        // Check repository-level access first
        if ($token->hasAccessToRepository($repository)) {
            return;
        }

        // For DeployTokens, also check if they have package-level access to any package in this repository
        if ($token instanceof DeployToken && $this->hasPackageLevelAccess($token, $repository)) {
            return;
        }

        abort(401);
    }

    /**
     * Check if a DeployToken has access to any package in the repository.
     */
    private function hasPackageLevelAccess(DeployToken $token, Repository $repository): bool
    {
        return $token->packages()
            ->where('repository_id', $repository->id)
            ->exists();
    }

    /**
     * Abort with 404 if the token does not have access to the package.
     *
     * For public repositories, access is always granted.
     * For private repositories, the token must have access to the package.
     *
     * Uses 404 to avoid leaking package existence information.
     */
    protected function abortIfNoPackageAccess(Package $package): void
    {
        $repository = $this->repository();

        if ($repository->public) {
            return;
        }

        $token = $this->token();

        if ($token === null || ! $token->hasAccessToPackage($package)) {
            abort(404);
        }
    }
}
