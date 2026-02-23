<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Models\Contracts\Tokenable;
use App\Models\Package;
use App\Models\Scopes\TokenPackageScope;
use App\Models\Scopes\UserPackageScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Package>
 */
class PackageBuilder extends Builder
{
    public function tokenScoped(?Tokenable $token): PackageBuilder
    {
        return new TokenPackageScope($token)->apply($this);
    }

    public function userScoped(?User $user = null): PackageBuilder
    {
        /** @var User|null $user */
        $user ??= auth()->user();

        return new UserPackageScope($user)->apply($this);
    }
}
