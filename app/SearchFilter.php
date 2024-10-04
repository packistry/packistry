<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<Model>
 */
final class SearchFilter implements Filter
{
    /**
     * @param  string[]  $columns
     */
    public function __construct(public array $columns)
    {
        //
    }

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $query->where(function (Builder $query) use ($value): void {
            foreach ($this->columns as $index => $column) {
                $index === 0
                    ? $query->whereLike($column, "%$value%")
                    : $query->orWhereLike($column, "%$value%");
            }
        });
    }

    /**
     * @param  string[]  $columns
     */
    public static function allowed(array $columns, string $name = 'search'): AllowedFilter
    {
        return AllowedFilter::callback($name, new self(columns: $columns));
    }
}
