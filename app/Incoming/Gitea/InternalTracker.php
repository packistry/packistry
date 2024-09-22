<?php

declare(strict_types=1);

namespace App\Incoming\Gitea;

class InternalTracker extends Input
{
    public function __construct(
        public bool $enableTimeTracker,
        public bool $allowOnlyContributorsToTrackTime,
        public bool $enableIssueDependencies
    ) {}
}
