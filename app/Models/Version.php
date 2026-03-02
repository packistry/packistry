<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\VersionOrderScope;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Database\Factories\VersionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $package_id
 * @property string $name
 * @property array<array-key, mixed> $metadata
 * @property string $shasum
 * @property string $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $total_downloads
 * @property string|null $archive_path
 * @property-read Collection<int, Download> $downloads
 * @property-read int|null $downloads_count
 * @property-read Package $package
 *
 * @method static VersionFactory factory($count = null, $state = [])
 * @method static Builder<static>|Version newModelQuery()
 * @method static Builder<static>|Version newQuery()
 * @method static Builder<static>|Version query()
 *
 * @mixin Eloquent
 */
#[ScopedBy(VersionOrderScope::class)]
class Version extends Model
{
    /** @use HasFactory<VersionFactory> */
    use HasFactory;

    protected $casts = [
        'metadata' => 'json',
    ];

    protected $guarded = [];

    protected $hidden = ['order'];

    protected $attributes = [
        'total_downloads' => 0,
    ];

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return HasMany<Download, $this>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    #[Override]
    protected static function booted(): void
    {
        static::created(function (Version $version): void {
            if (! $version->isStable()) {
                return;
            }

            $currentLatest = $version->package->latest_version;

            if ($currentLatest !== null && Comparator::greaterThanOrEqualTo($currentLatest, $version->name)) {
                return;
            }

            $version->package->latest_version = $version->name;
            $version->package->save();
        });
    }

    private function isStable(): bool
    {
        $parser = new VersionParser;

        return $parser->parseStability($this->name) === 'stable';
    }
}
