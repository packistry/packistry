<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SourceProvider;
use App\Import;
use App\Sources\Client;
use Database\Factories\SourceFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * @property int $id
 * @property string $name
 * @property SourceProvider $provider
 * @property string $url
 * @property string $token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static SourceFactory factory($count = null, $state = [])
 * @method static Builder|Source newModelQuery()
 * @method static Builder|Source newQuery()
 * @method static Builder|Source query()
 *
 * @mixin Eloquent
 */
class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    protected $casts = [
        'provider' => SourceProvider::class,
    ];

    public function client(): Client
    {
        $class = config()->string("services.{$this->provider->value}.client");

        if (! is_subclass_of($class, Client::class)) {
            throw new RuntimeException($class.' has to implement '.Client::class);
        }

        $token = decrypt($this->token);

        return new $class($this->url, $token, app(Import::class));
    }
}
