<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\Permission;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

readonly class UserPackageScope implements Scope
{
    public function __construct(private ?User $user)
    {
        //
    }

    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! $this->user instanceof User) {
            abort(401);
        }

        if ($this->user->can(Permission::UNSCOPED)) {
            return;
        }

        $builder->where(function (Builder $query): void {
            $query->whereIn('repository_id', $this->user->accessibleRepositoryIdsQuery()
                ->union(Repository::query()->public()->select('id')->toBase()))
                ->orWhereIn('id', $this->user->accessiblePackageIdsQuery());
        });
    }
}
