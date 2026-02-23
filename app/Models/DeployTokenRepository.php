<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $deploy_token_id
 * @property int $repository_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DeployToken $deployToken
 * @property-read Repository $repository
 *
 * @method static Builder<static>|DeployTokenRepository newModelQuery()
 * @method static Builder<static>|DeployTokenRepository newQuery()
 * @method static Builder<static>|DeployTokenRepository query()
 *
 * @mixin Eloquent
 */
class DeployTokenRepository extends Pivot
{
    public $incrementing = true;

    /**
     * @return BelongsTo<DeployToken, $this>
     */
    public function deployToken(): BelongsTo
    {
        return $this->belongsTo(DeployToken::class);
    }

    /**
     * @return BelongsTo<Repository, $this>
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }
}
