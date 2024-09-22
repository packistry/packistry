<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Repository;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

readonly class PackageDownloadEvent
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Repository $repository,
        public string $vendor,
        public string $name,
        public string $version,
        public string $ip,
        public ?User $user
    ) {
        //
    }
}
