<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PackageSourceProvider;
use Database\Factories\PackageSourceFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * @property int $id
 * @property string $name
 * @property PackageSourceProvider $provider
 * @property string $url
 * @property string $token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static PackageSourceFactory factory($count = null, $state = [])
 * @method static Builder|PackageSource newModelQuery()
 * @method static Builder|PackageSource newQuery()
 * @method static Builder|PackageSource query()
 *
 * @mixin Eloquent
 */
class PackageSource extends Model
{
    /** @use HasFactory<PackageSourceFactory> */
    use HasFactory;

    protected $casts = [
        'provider' => PackageSourceProvider::class,
    ];

    public function client(): PendingRequest
    {
        $token = decrypt($this->token);

        $http = Http::baseUrl($this->url);

        return match ($this->provider) {
            PackageSourceProvider::GITLAB => $http->withHeader('Private-Token', $token),
            PackageSourceProvider::GITEA, PackageSourceProvider::GITHUB => $http->withHeader('Authorization', "Bearer $token"),
        };
    }
}
