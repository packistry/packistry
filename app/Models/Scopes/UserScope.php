<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\Permission;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

readonly class UserScope implements Scope
{
    public function __construct(private ?User $user, private string $column = 'repository_id')
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

        if ($this->column === 'id') {
            $builder->whereIn('id', $this->user->accessibleRepositoryIdsQuery()
                ->union(Repository::query()->select('id')->where('public', true)->toBase()));

            return;
        }

        $builder->where(function (Builder $query): void {
            $query->whereIn($this->column, $this->user->accessibleRepositoryIdsQuery()->union(Repository::query()->select('id')->where('public', true)->toBase()))
                ->orWhereIn('id', $this->user->accessiblePackageIdsQuery());
        });
    }
}
