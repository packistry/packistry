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
 * @property int $package_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DeployToken $deployToken
 * @property-read Package $package
 *
 * @method static Builder<static>|DeployTokenPackage newModelQuery()
 * @method static Builder<static>|DeployTokenPackage newQuery()
 * @method static Builder<static>|DeployTokenPackage query()
 *
 * @mixin Eloquent
 */
class DeployTokenPackage extends Pivot
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
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
