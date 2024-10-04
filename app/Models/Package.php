<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PackageType;
use App\Models\Scopes\UserScope;
use Database\Factories\PackageFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $repository_id
 * @property int|null $source_id
 * @property string $provider_id
 * @property string $name
 * @property string|null $latest_version
 * @property PackageType $type
 * @property string|null $description
 * @property int $downloads
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Repository $repository
 * @property-read Source|null $source
 * @property-read Collection<int, Version> $versions
 * @property-read int|null $versions_count
 *
 * @method static PackageFactory factory($count = null, $state = [])
 * @method static Builder|Package newModelQuery()
 * @method static Builder|Package newQuery()
 * @method static Builder|Package query()
 *
 * @mixin Eloquent
 */
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    protected $casts = [
        'type' => PackageType::class,
    ];

    /**
     * @phpstan-ignore-next-line
     */
    protected $attributes = [
        'downloads' => 0,
    ];

    /**
     * @return BelongsTo<Repository, Package>
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    /**
     * @return BelongsTo<Source, Package>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * @return HasMany<Version>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Version::class);
    }

    /**
     * @return Builder<Package>
     */
    public static function userScoped(?User $user = null): Builder
    {
        /** @var User|null $user */
        $user ??= auth()->user();

        return self::query()
            ->withGlobalScope('user', new UserScope($user));
    }
}
