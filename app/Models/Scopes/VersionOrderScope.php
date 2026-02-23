<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

readonly class VersionOrderScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->orderByDesc('order');
    }
}
