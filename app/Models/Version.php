<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\OrderScope;
use Database\Factories\VersionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    protected static function booted(): void
    {
        static::addGlobalScope(new OrderScope('order'));
        static::creating(function (Version $version): void {
            $version->order = Str::of($version->name)
                ->explode('.')
                ->map(fn ($part) => Str::padLeft($part, 3, '0'))
                ->implode('.');
        });
    }
}
