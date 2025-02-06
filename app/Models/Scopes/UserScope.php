<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\Permission;
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

        $builder->whereIn($this->column, function (\Illuminate\Database\Query\Builder $query): void {
            $query->select('repository_id')
                ->from('repository_user')
                ->where('user_id', $this->user?->id)
                ->union(function (\Illuminate\Database\Query\Builder $query): void {
                    $query->select('id')
                        ->from('repositories')
                        ->where('public', true);
                });
        });
    }
}
