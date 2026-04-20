<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Version;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @implements Scope<Version>
 */
readonly class VersionOrderScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->orderByDesc('order');
    }
}
