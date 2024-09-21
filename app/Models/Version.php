<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\OrderScope;
use Database\Factories\VersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Version extends Model
{
    /** @use HasFactory<VersionFactory> */
    use HasFactory;

    protected $casts = [
        'metadata' => 'json',
    ];

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
        static::creating(function (Version $version) {
            $version->order = Str::of($version->name)
                ->explode('.')
                ->map(function ($part) {
                    return Str::padLeft($part, 3, '0');
                })
                ->implode('.');
        });
    }
}
