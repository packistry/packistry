<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PackageType;
use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\FailedToOpenArchiveException;
use App\Exceptions\NameNotFoundException;
use App\Exceptions\VersionNotFoundException;
use App\Models\Scopes\UserScope;
use App\Sources\Importable;
use Database\Factories\PackageFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $repository_id
 * @property int|null $source_id
 * @property string|null $provider_id
 * @property string $name
 * @property string|null $latest_version
 * @property PackageType $type
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
 * @method static Builder<static>|Package newModelQuery()
 * @method static Builder<static>|Package newQuery()
 * @method static Builder<static>|Package query()
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
     * @return Builder<$this>
     */
    public static function userScoped(?User $user = null): Builder
    {
        /** @var User|null $user */
        $user ??= auth()->user();

        return self::query()
            ->withGlobalScope('user', new UserScope($user));
    }

    /**
     * @throws FailedToFetchArchiveException
     * @throws ArchiveInvalidContentTypeException
     * @throws FailedToOpenArchiveException
     * @throws ComposerJsonNotFoundException
     * @throws NameNotFoundException
     * @throws VersionNotFoundException
     * @throws ConnectionException
     */
    public function import(Importable $importable): Version
    {
        return $this->source->client()->import(
            package: $this,
            importable: $importable,
        );
    }
}
