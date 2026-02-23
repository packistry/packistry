<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Models\Repository;
use App\Models\Scopes\UserPackageScope;
use App\Models\Scopes\UserRepositoryScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Repository>
 */
class RepositoryBuilder extends Builder
{
    public function public(bool $public = true): static
    {
        return $this->where('public', $public);
    }

    public function userScoped(?User $user = null): static
    {
        /** @var User|null $user */
        $user ??= auth()->user();

        return $this->withGlobalScope('user', new UserRepositoryScope($user));
    }

    public function withUserScopedPackageCount(?User $user = null): static
    {
        /** @var User|null $user */
        $user ??= auth()->user();

        return $this->withCount([
            'packages' => fn (Builder $packagesQuery) => $packagesQuery->withGlobalScope('user', new UserPackageScope($user)),
        ]);
    }
}
