<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
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
 * @mixin Eloquent
 */
class DeployTokenRepository extends Pivot
{
    protected $table = 'deploy_token_repository';

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
