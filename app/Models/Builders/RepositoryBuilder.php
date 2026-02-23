<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Models\Contracts\Tokenable;
use App\Models\Repository;
use App\Models\Scopes\TokenRepositoryScope;
use App\Models\Scopes\UserRepositoryScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Repository>
 */
class RepositoryBuilder extends Builder
{
    public function tokenScoped(?Tokenable $token): static
    {
        new TokenRepositoryScope(token: $token)
            ->apply($this);

        return $this;
    }

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
            'packages' => fn (PackageBuilder $packagesQuery) => $packagesQuery->userScoped($user),
        ]);
    }

    public function queryByPath(?string $path): RepositoryBuilder
    {
        return $this->when(
            $path,
            fn (\Illuminate\Contracts\Database\Eloquent\Builder $query) => $query->where('path', $path),
            fn (Builder $query) => $query->whereNull('path')
        );
    }
}
