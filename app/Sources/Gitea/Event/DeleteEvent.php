<?php

declare(strict_types=1);

namespace App\Sources\Gitea\Event;

use App\Sources\Deletable;
use App\Sources\Gitea\Input;
use App\Sources\Gitea\Repository;

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
