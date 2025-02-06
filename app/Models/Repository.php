<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\UserScope;
use Database\Factories\RepositoryFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @method static Builder|Repository newModelQuery()
 * @method static Builder|Repository newQuery()
 * @method static Builder|Repository query()
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

    public function url(string $url): string
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
     * @return Builder<$this>
     */
    public static function userScoped(?User $user = null): Builder
    {
        /** @var User|null $user */
        $user ??= auth()->user();

        return self::query()
            ->withGlobalScope('user', new UserScope($user, 'id'));
    }

    /**
     * @return Builder<$this>
     */
    public static function queryByPath(?string $path): Builder
    {
        return self::query()->when(
            $path,
            fn (\Illuminate\Contracts\Database\Eloquent\Builder $query) => $query->where('path', $path),
            fn (Builder $query) => $query->whereNull('path')
        );
    }

    public static function isPathInUse(?string $path, ?int $exclude = null): bool
    {
        return self::queryByPath($path)
            ->when($exclude, fn (Builder $query) => $query->whereNot('id', $exclude))
            ->exists();
    }
}
