<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Builders\RepositoryBuilder;
use Database\Factories\RepositoryFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $path
 * @property string|null $description
 * @property bool $public
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Package> $packages
 * @property-read int|null $packages_count
 *
 * @method static RepositoryFactory factory($count = null, $state = [])
 * @method static RepositoryBuilder newModelQuery()
 * @method static RepositoryBuilder newQuery()
 * @method static RepositoryBuilder query()
 * @method static RepositoryBuilder userScoped(?User $user = null)
 * @method static RepositoryBuilder withUserScopedPackageCount(?User $user = null)
 *
 * @mixin Eloquent
 */
class Repository extends Model
{
    /** @use HasFactory<RepositoryFactory> */
    use HasFactory;

    protected $casts = [
        'public' => 'bool',
    ];

    /**
     * @return HasMany<Package, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function url(string $url = ''): string
    {
        $prefix = is_null($this->path) ? '' : "/r/$this->path";

        return url($prefix.$url);
    }

    public function archivePath(string $file): string
    {
        $prefix = is_null($this->path) ? '' : "$this->path/";

        return $prefix.basename($file);
    }

    public function packageByName(string $name): ?Package
    {
        /** @var Package|null $package */
        $package = $this->packages()
            ->where('name', $name)
            ->first();

        $package?->setRelation('repository', $this);

        return $package;
    }

    public function packageByNameOrFail(string $name): Package
    {
        return $this->packageByName($name) ?? throw new ModelNotFoundException;
    }

    /**
     * @param  QueryBuilder  $query
     */
    public function newEloquentBuilder($query): RepositoryBuilder
    {
        return new RepositoryBuilder($query);
    }

    public static function isPathInUse(?string $path, ?int $exclude = null): bool
    {
        return self::query()->queryByPath($path)
            ->when($exclude, fn (Builder $query) => $query->whereNot('id', $exclude))
            ->exists();
    }
}
