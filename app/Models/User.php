<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Contracts\Tokenable;
use App\Models\Traits\HasApiTokens;
use Database\Factories\UserFactory;
use Eloquent;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * @property int $id
 * @property string $name
 * @property Role $role
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $external_id
 * @property int|null $authentication_source_id
 * @property-read AuthenticationSource|null $authenticationSource
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Package> $packages
 * @property-read int|null $packages_count
 * @property-read Collection<int, PackageUser> $packageUsers
 * @property-read int|null $package_users_count
 * @property-read Collection<int, Repository> $repositories
 * @property-read int|null $repositories_count
 * @property-read Collection<int, RepositoryUser> $repositoryUsers
 * @property-read int|null $repository_users_count
 * @property-read Collection<int, Token> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User query()
 *
 * @mixin Eloquent
 */
class User extends Model implements AuthenticatableContract, Tokenable
{
    use Authenticatable;
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
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
     * @return HasMany<PackageUser, $this>
     */
    public function packageUsers(): HasMany
    {
        return $this->hasMany(PackageUser::class);
    }

    /**
     * @return HasMany<RepositoryUser, $this>
     */
    public function repositoryUsers(): HasMany
    {
        return $this->hasMany(RepositoryUser::class);
    }

    /**
     * @return BelongsTo<AuthenticationSource, $this>
     */
    public function authenticationSource(): BelongsTo
    {
        return $this->belongsTo(AuthenticationSource::class);
    }

    public function can(Permission $permission): bool
    {
        return in_array($permission, $this->role->permissions(), true);
    }

    public function canNot(Permission $permission): bool
    {
        return ! $this->can($permission);
    }

    public function hasAccessToRepository(int|Repository $repository): bool
    {
        if ($this->isUnscoped()) {
            return true;
        }

        $repositoryId = is_int($repository) ? $repository : $repository->id;

        if ($this->repositories()->where('repositories.id', $repositoryId)->exists()) {
            return true;
        }

        return $this->packages()->where('packages.repository_id', $repositoryId)->exists();
    }

    public function accessibleRepositoryIdsQuery(): QueryBuilder
    {
        return $this->isUnscoped()
            ? throw new RuntimeException('Should be skipped when unscoped')
            : Repository::query()
                ->select('repositories.id')
                ->public()
                ->union($this->repositoryUsers()->select('repository_id')->toBase())
                ->union($this->packages()->select('repository_id')->distinct()->toBase())
                ->toBase();
    }

    public function accessiblePackageIdsQuery(): QueryBuilder
    {
        return $this->isUnscoped()
            ? throw new RuntimeException('Should be skipped when unscoped')
            : Package::query()
                ->whereIn('packages.repository_id', Repository::query()->public()->select('id')
                    ->union($this->repositoryUsers()->select('repository_id')->toBase())
                )
                ->select('packages.id')
                ->union($this->packageUsers()->select('package_id')->toBase())
                ->toBase();
    }

    public function isUnscoped(): bool
    {
        return $this->can(Permission::UNSCOPED);
    }

    public function hasAccessToPackage(Package $package): bool
    {
        if ($this->isUnscoped()) {
            return true;
        }

        return $this->hasAccessToRepository($package->repository_id)
            || $this->packages()->where('packages.id', $package->id)->exists();
    }

    public static function isEmailInUse(?string $email, ?int $exclude = null): bool
    {
        return self::query()
            ->where('email', $email)
            ->when($exclude, fn (Builder $query) => $query->whereNot('id', $exclude))
            ->exists();
    }
}
