<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UpstreamFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string[]|null $only
 * @property string[]|null $exclude
 * @property string|null $metadata_url
 * @property Carbon|null $root_checked_at
 * @property string|null $last_error
 * @property Carbon|null $last_error_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, UpstreamCredential> $credentials
 * @property-read int|null $credentials_count
 * @property-read Collection<int, Repository> $repositories
 * @property-read int|null $repositories_count
 * @property-read Collection<int, Package> $packages
 * @property-read int|null $packages_count
 * @property-read Collection<int, MirrorDist> $dists
 * @property-read int|null $dists_count
 * @property-read RepositoryUpstream|null $pivot
 *
 * @method static UpstreamFactory factory($count = null, $state = [])
 *
 * @mixin Eloquent
 */
class Upstream extends Model
{
    /** @use HasFactory<UpstreamFactory> */
    use HasFactory;

    protected $casts = [
        'only' => 'array',
        'exclude' => 'array',
        'root_checked_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    /**
     * @return HasMany<UpstreamCredential, $this>
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(UpstreamCredential::class);
    }

    /**
     * @return BelongsToMany<Repository, $this, RepositoryUpstream>
     */
    public function repositories(): BelongsToMany
    {
        return $this->belongsToMany(Repository::class)
            ->using(RepositoryUpstream::class)
            ->withPivot(['priority', 'enabled'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Package, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /**
     * @return HasMany<MirrorDist, $this>
     */
    public function dists(): HasMany
    {
        return $this->hasMany(MirrorDist::class);
    }

    /**
     * Whether the package name passes this upstream's only/exclude filters.
     */
    public function allows(string $name): bool
    {
        $only = $this->only ?? [];
        $exclude = $this->exclude ?? [];

        if ($only !== [] && ! $this->matchesAny($name, $only)) {
            return false;
        }

        return ! $this->matchesAny($name, $exclude);
    }

    public function host(): ?string
    {
        $host = parse_url($this->url, PHP_URL_HOST);

        return is_string($host) ? Str::lower($host) : null;
    }

    /**
     * An upstream is private when it authenticates against its own host,
     * credentials for other hosts (e.g. a GitHub token for dists) do not count.
     */
    public function isPrivate(): bool
    {
        $host = $this->host();

        return $this->credentials->contains(
            fn (UpstreamCredential $credential): bool => $credential->host === $host
        );
    }

    public function storagePath(string $path = ''): string
    {
        return rtrim("_mirror/$this->id/$path", '/');
    }

    /**
     * @param  string[]  $patterns
     */
    private function matchesAny(string $name, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $regex = '{^'.str_replace('\*', '.*', preg_quote($pattern, '{')).'$}i';

            if (preg_match($regex, $name) === 1) {
                return true;
            }
        }

        return false;
    }
}
