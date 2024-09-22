<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\VersionNotFoundException;
use App\Models\Scopes\OrderScope;
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

/**
 * @property int $id
 * @property int $package_id
 * @property string $name
 * @property array $metadata
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

    protected $hidden = ['order'];

    /**
     * @return BelongsTo<Package, Version>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return HasMany<Download>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OrderScope('order'));
        static::creating(function (Version $version): void {
            if (str_starts_with($version->name, 'dev-')) {
                $version->order = $version->name;

                return;
            }

            if (preg_match('/\d+\.\d+\.\d+/', $version->name, $matches) === 0 || preg_match('/\d+\.\d+\.\d+/', $version->name, $matches) === false) {
                throw new VersionNotFoundException;
            }

            $version->name = $matches[0];

            $version->order = Str::of($version->name)
                ->explode('.')
                ->map(fn (string $part) => Str::padLeft($part, 3, '0'))
                ->implode('.');
        });
    }
}
