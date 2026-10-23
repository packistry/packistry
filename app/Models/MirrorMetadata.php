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
 * @property bool $dev
 * @property string $body_path
 * @property string|null $last_modified
 * @property Carbon|null $fetched_at
 * @property Carbon|null $checked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Upstream $upstream
 *
 * @mixin Eloquent
 */
class MirrorMetadata extends Model
{
    protected $table = 'mirror_metadata';

    protected $casts = [
        'dev' => 'bool',
        'fetched_at' => 'datetime',
        'checked_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Upstream, $this>
     */
    public function upstream(): BelongsTo
    {
        return $this->belongsTo(Upstream::class);
    }
}
