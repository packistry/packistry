<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Enums\MirrorStatus;
use App\Models\Contracts\Tokenable;
use App\Models\Package;
use App\Models\Scopes\TokenPackageScope;
use App\Models\Scopes\UserPackageScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Package>
 */
class PackageBuilder extends Builder
{
    public function tokenScoped(?Tokenable $token = null): PackageBuilder
    {
        return new TokenPackageScope($token)->apply($this);
    }

    public function userScoped(?User $user = null): PackageBuilder
    {
        return new UserPackageScope($user)->apply($this);
    }

    public function local(): PackageBuilder
    {
        return $this->whereNull('mirror_status');
    }

    public function mirrored(): PackageBuilder
    {
        return $this->whereNotNull('mirror_status');
    }

    /**
     * Excludes mirrored packages that are no longer available upstream.
     */
    public function withoutGone(): PackageBuilder
    {
        return $this->where(fn (Builder $query) => $query
            ->whereNull('mirror_status')
            ->orWhere('mirror_status', '!=', MirrorStatus::GONE->value));
    }
}
