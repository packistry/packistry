<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\OrderScope;
use App\Normalizer;
use Database\Factories\VersionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Override;

/**
 * @property int $id
 * @property int $package_id
 * @property string $name
 * @property array<string, mixed> $metadata
 * @property string $shasum
 * @property string $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Download> $downloads
 * @property-read int|null $downloads_count
 * @property-read Package $package
 *
 * @method static VersionFactory factory($count = null, $state = [])
 * @method static Builder|Version newModelQuery()
 * @method static Builder|Version newQuery()
 * @method static Builder|Version query()
 *
 * @mixin Eloquent
 */
class Version extends Model
{
    /** @use HasFactory<VersionFactory> */
    use HasFactory;

    protected $casts = [
        'metadata' => 'json',
    ];

    protected $guarded = [];

    protected $hidden = ['order', 'original'];

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
        static::addGlobalScope(new OrderScope('order'));
        static::creating(function (Version $version): void {
            $version->name = Normalizer::version($version->name);

            $order = str_starts_with($version->name, 'dev-')
                ? $version->name
                : Str::of($version->name)
                    ->explode('.')
                    ->map(fn (string $part) => Str::padLeft($part, 3, '0'))
                    ->implode('.');

            $version->order = $order;
        });

        static::created(function (Version $version): void {
            if ($version->isDev()) {
                return;
            }

            $version->package->latest_version = $version->name;
            $version->package->save();
        });
    }

    private function isDev(): bool
    {
        return str_starts_with($this->package->name, 'dev-');
    }
}
