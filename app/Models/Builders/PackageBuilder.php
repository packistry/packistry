<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Models\Package;
use App\Models\Scopes\UserScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Package>
 */
class PackageBuilder extends Builder
{
    public function userScoped(?User $user = null): static
    {
        /** @var User|null $user */
        $user ??= auth()->user();

        return $this->withGlobalScope('user', new UserScope($user));
    }
}
