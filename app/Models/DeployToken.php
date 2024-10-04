<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contracts\Tokenable;
use App\Models\Traits\HasApiTokens;
use Database\Factories\DeployTokenFactory;
use Eloquent;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Token|null $token
 * @property-read Collection<int, Token> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static Builder|DeployToken newModelQuery()
 * @method static Builder|DeployToken newQuery()
 * @method static Builder|DeployToken query()
 *
 * @mixin Eloquent
 */
class DeployToken extends Model implements AuthenticatableContract, Tokenable
{
    use Authenticatable;
    use HasApiTokens;

    /** @use HasFactory<DeployTokenFactory> */
    use HasFactory;

    /**
     * @return MorphOne<Token>
     */
    public function token(): MorphOne
    {
        return $this->morphOne(Token::class, 'tokenable')->latestOfMany();
    }

    /**
     * @return BelongsToMany<Repository>
     */
    public function repositories(): BelongsToMany
    {
        return $this->belongsToMany(Repository::class);
    }

    public function hasAccessToRepository(Repository $repository): bool
    {
        return $this->repositories()->where('repositories.id', $repository->id)->exists();
    }
}
