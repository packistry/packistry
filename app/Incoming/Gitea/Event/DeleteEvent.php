<?php

declare(strict_types=1);

namespace App\Incoming\Gitea\Event;

use App\Incoming\Gitea\Input;
use App\Incoming\Gitea\Repository;

class DeleteEvent extends Input
{
    public function __construct(
        public string $ref,
        public string $refType,
        public string $pusherType,
        public Repository $repository,
    ) {}
}
