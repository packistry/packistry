<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\OrderScope;
use Database\Factories\VersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $package_id
 * @property string $name
 * @property array $metadata
 * @property string $shasum
 * @property string $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Package $package
 *
 * @method static \Database\Factories\VersionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Version newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Version newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Version query()
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Version wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereShasum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereUpdatedAt($value)
 *
 * @mixin \Eloquent
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
