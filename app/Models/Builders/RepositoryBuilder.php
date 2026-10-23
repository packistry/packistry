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
    public function tokenScoped(?Tokenable $token = null): RepositoryBuilder
    {
        return new TokenRepositoryScope($token)->apply($this);
    }

    public function public(bool $public = true): RepositoryBuilder
    {
        return $this->where('public', $public);
    }

    public function userScoped(?User $user = null): RepositoryBuilder
    {
        return new UserRepositoryScope($user)->apply($this);
    }

    public function withUserScopedPackageCount(?User $user = null): RepositoryBuilder
    {
        return $this->withCount([
            // mirrored packages are not counted, they are hidden from package listings by default
            'packages' => fn (PackageBuilder $packagesQuery) => $packagesQuery->userScoped($user)->local(),
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
