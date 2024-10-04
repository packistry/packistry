<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DownloadFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $version_id
 * @property int|null $token_id
 * @property string|null $ip
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static DownloadFactory factory($count = null, $state = [])
 * @method static Builder|Download newModelQuery()
 * @method static Builder|Download newQuery()
 * @method static Builder|Download query()
 *
 * @mixin Eloquent
 */
class Download extends Model
{
    /** @use HasFactory<DownloadFactory> */
    use HasFactory;
}
