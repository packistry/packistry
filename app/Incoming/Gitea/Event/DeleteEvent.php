<?php

declare(strict_types=1);

namespace App\Incoming\Gitea\Event;

use App\Incoming\Deletable;
use App\Incoming\Gitea\Input;
use App\Incoming\Gitea\Repository;

class DeleteEvent extends Input implements Deletable
{
    public function __construct(
        public string $ref,
        public string $refType,
        public string $pusherType,
        public Repository $repository,
    ) {}

    public function version(): string
    {
        if ($this->refType === 'branch') {
            return "dev-$this->ref";
        }

        return $this->ref;
    }

    public function name(): string
    {
        return $this->repository->fullName;
    }
}
