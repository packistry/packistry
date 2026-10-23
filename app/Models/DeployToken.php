<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contracts\Tokenable;
use App\Models\Traits\HasApiTokens;
use Database\Factories\DeployTokenFactory;
use Eloquent;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DeployTokenPackage> $deployTokenPackages
 * @property-read int|null $deploy_token_packages_count
 * @property-read Collection<int, DeployTokenRepository> $deployTokenRepositories
 * @property-read int|null $deploy_token_repositories_count
 * @property-read Collection<int, Package> $packages
 * @property-read int|null $packages_count
 * @property-read Collection<int, Upstream> $upstreams
 * @property-read int|null $upstreams_count
 * @property-read Collection<int, Repository> $repositories
 * @property-read int|null $repositories_count
 * @property-read Token|null $token
 * @property-read Collection<int, Token> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static DeployTokenFactory factory($count = null, $state = [])
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
     * @return BelongsToMany<Upstream, $this>
     */
    public function upstreams(): BelongsToMany
    {
        return $this->belongsToMany(Upstream::class)->withTimestamps();
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
            ->union(RepositoryUpstream::query()
                ->whereIn('upstream_id', $this->upstreamIdsQuery())
                ->select('repository_id')
                ->toBase())
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
            ->union(Package::query()
                ->whereIn('upstream_id', $this->upstreamIdsQuery())
                ->select('packages.id')
                ->toBase())
            ->toBase();
    }

    private function upstreamIdsQuery(): QueryBuilder
    {
        return DB::table('deploy_token_upstream')
            ->where('deploy_token_id', $this->id)
            ->select('upstream_id');
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
