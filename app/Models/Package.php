<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Builders\PackageBuilder;
use App\Models\Contracts\Tokenable;
use Database\Factories\PackageFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $repository_id
 * @property int|null $source_id
 * @property string|null $provider_id
 * @property string $name
 * @property string|null $latest_version
 * @property string $type
 * @property string|null $description
 * @property int $total_downloads
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Repository $repository
 * @property-read Source|null $source
 * @property-read Collection<int, Version> $versions
 * @property-read int|null $versions_count
 *
 * @method static PackageFactory factory($count = null, $state = [])
 * @method static PackageBuilder newModelQuery()
 * @method static PackageBuilder newQuery()
 * @method static PackageBuilder query()
 * @method static PackageBuilder tokenScoped(?Tokenable $token, Repository $repository)
 * @method static PackageBuilder userScoped(?User $user = null)
 *
 * @mixin Eloquent
 */
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    protected $attributes = [
        'total_downloads' => 0,
    ];

    /**
     * @return BelongsTo<Repository, $this>
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * @return HasMany<Version, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Version::class);
    }

    /**
     * @param  QueryBuilder  $query
     */
    public function newEloquentBuilder($query): PackageBuilder
    {
        return new PackageBuilder($query);
    }
}
