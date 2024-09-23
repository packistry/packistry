<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RepositoryFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $name
 * @property bool $public
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Package> $packages
 * @property-read int|null $packages_count
 *
 * @method static RepositoryFactory factory($count = null, $state = [])
 * @method static Builder|Repository newModelQuery()
 * @method static Builder|Repository newQuery()
 * @method static Builder|Repository query()
 *
 * @mixin Eloquent
 */
class Repository extends Model
{
    /** @use HasFactory<RepositoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<Package>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function url(string $url): string
    {
        $prefix = is_null($this->name) ? '' : "/$this->name";

        return $prefix.$url;
    }

    public function archivePath(string $file): string
    {
        $prefix = is_null($this->name) ? '' : "$this->name/";

        return $prefix.basename($file);
    }
}
