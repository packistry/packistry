<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\Permission;
use App\Models\Builders\RepositoryBuilder;
use App\Models\User;

readonly class UserRepositoryScope
{
    public function __construct(private ?User $user)
    {
        //
    }

    public function apply(RepositoryBuilder $builder): RepositoryBuilder
    {
        if (! $this->user instanceof User) {
            abort(401);
        }

        if ($this->user->can(Permission::UNSCOPED)) {
            return $builder;
        }

        return $builder->whereIn('id', $this->user->accessibleRepositoryIdsQuery());
    }
}
