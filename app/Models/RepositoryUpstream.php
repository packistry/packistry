<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $repository_id
 * @property int $upstream_id
 * @property int $priority
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin Eloquent
 */
class RepositoryUpstream extends Pivot
{
    public $incrementing = true;

    protected $table = 'repository_upstream';

    protected $casts = [
        'priority' => 'int',
        'enabled' => 'bool',
    ];
}
