<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
