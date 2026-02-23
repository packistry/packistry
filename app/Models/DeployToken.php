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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Package> $packages
 * @property-read Collection<int, DeployTokenPackage> $deployTokenPackages
 * @property-read int|null $deploy_token_packages_count
 * @property-read Collection<int, DeployTokenRepository> $deployTokenRepositories
 * @property-read int|null $deploy_token_repositories_count
 * @property-read int|null $packages_count
 * @property-read Collection<int, Repository> $repositories
 * @property-read int|null $repositories_count
 * @property-read Token|null $token
 * @property-read Collection<int, Token> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static DeployTokenFactory factory($count = null, $state = [])
 * @method static Builder<static>|DeployToken newModelQuery()
 * @method static Builder<static>|DeployToken newQuery()
 * @method static Builder<static>|DeployToken query()
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
     * @return MorphOne<Token, $this>
     */
    public function token(): MorphOne
    {
        return $this->morphOne(Token::class, 'tokenable')->latestOfMany();
    }

    /**
     * @return BelongsToMany<Repository, $this>
     */
    public function repositories(): BelongsToMany
    {
        return $this->belongsToMany(Repository::class);
    }

    /**
     * @return BelongsToMany<Package, $this>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class);
    }

    /**
     * @return HasMany<DeployTokenRepository, $this>
     */
    public function deployTokenRepositories(): HasMany
    {
        return $this->hasMany(DeployTokenRepository::class);
    }

    /**
     * @return HasMany<DeployTokenPackage, $this>
     */
    public function deployTokenPackages(): HasMany
    {
        return $this->hasMany(DeployTokenPackage::class);
    }

    public function accessibleRepositoryIdsQuery(): QueryBuilder
    {
        return Repository::query()
            ->select('repositories.id')
            ->public()
            ->union($this->deployTokenRepositories()->select('repository_id')->toBase())
            ->union($this->packages()->select('repository_id')->distinct()->toBase())
            ->toBase();
    }

    public function accessiblePackageIdsQuery(): QueryBuilder
    {
        return Package::query()
            ->whereIn('packages.repository_id', Repository::query()->public()->select('id')
                ->union($this->deployTokenRepositories()->select('repository_id')->toBase())
            )
            ->select('packages.id')
            ->union($this->deployTokenPackages()->select('package_id')->toBase())
            ->toBase();
    }

    public function isUnscoped(): bool
    {
        return false;
    }

    public function hasAccessToPackage(Package $package): bool
    {
        return $this->accessibleRepositoryIdsQuery()->where('repositories.id', $package->repository_id)->exists()
            || $this->accessiblePackageIdsQuery()->where('packages.id', $package->id)->exists();
    }
}
