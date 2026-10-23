<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $upstream_id
 * @property string $name
 * @property string $reference
 * @property string $version
 * @property string $archive_path
 * @property string|null $shasum
 * @property int|null $size
 * @property Carbon|null $last_accessed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Upstream $upstream
 *
 * @mixin Eloquent
 */
class MirrorDist extends Model
{
    protected $casts = [
        'last_accessed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Upstream, $this>
     */
    public function upstream(): BelongsTo
    {
        return $this->belongsTo(Upstream::class);
    }
}
