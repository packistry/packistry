<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\Permission;
use App\Models\Builders\PackageBuilder;
use App\Models\User;

readonly class UserPackageScope
{
    public function __construct(private ?User $user)
    {
        //
    }

    public function apply(PackageBuilder $builder): PackageBuilder
    {
        if (! $this->user instanceof User) {
            abort(401);
        }

        if ($this->user->can(Permission::UNSCOPED)) {
            return $builder;
        }

        return $builder->whereIn('id', $this->user->accessiblePackageIdsQuery());
    }
}
