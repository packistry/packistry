<?php

declare(strict_types=1);

namespace App\Incoming\Gitea;

class RepositoryPermissions extends Input
{
    public function __construct(
        public bool $admin,
        public bool $push,
        public bool $pull
    ) {}
}
