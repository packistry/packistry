<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthenticationProvider;
use App\Enums\Role;
use App\OIDCProvider;
use Database\Factories\AuthenticationSourceFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Socialite\Two\ProviderInterface;

/**
 * @property int $id
 * @property string $name
 * @property AuthenticationProvider $provider
 * @property string $client_id
 * @property string $client_secret
 * @property string|null $icon_url
 * @property string|null $discovery_url
 * @property string|null $authorize_url
 * @property string|null $token_url
 * @property string|null $userinfo_url
 * @property Role $default_user_role
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Repository> $repositories
 * @property-read int|null $repositories_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static AuthenticationSourceFactory factory($count = null, $state = [])
 * @method static Builder<static>|AuthenticationSource newModelQuery()
 * @method static Builder<static>|AuthenticationSource newQuery()
 * @method static Builder<static>|AuthenticationSource query()
 *
 * @mixin Eloquent
 */
class AuthenticationSource extends Model
{
    /** @use HasFactory<AuthenticationSourceFactory> */
    use HasFactory;

    protected $casts = [
        'provider' => AuthenticationProvider::class,
        'default_user_role' => Role::class,
        'active' => 'boolean',
    ];

    protected $attributes = [
        'active' => true,
    ];

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function provider(Request $request): ProviderInterface
    {
        $class = $this->provider->socialiteProvider();

        if ($class === OIDCProvider::class) {
            return OIDCProvider::forSource(
                source: $this,
                request: $request
            );
        }

        return new $class(
            request: $request,
            clientId: $this->client_id,
            clientSecret: $this->client_secret,
            redirectUrl: $this->callbackUrl()
        );
    }

    /**
     * @return Builder<$this>
     */
    public static function active(): Builder
    {
        return self::query()
            ->where('active', true);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return BelongsToMany<Repository, $this>
     */
    public function repositories(): BelongsToMany
    {
        return $this->belongsToMany(Repository::class);
    }

    public function callbackUrl(): string
    {
        return url("/auths/$this->id/callback");
    }

    public function redirectUrl(): string
    {
        return url("/auths/$this->id/redirect");
    }
}
