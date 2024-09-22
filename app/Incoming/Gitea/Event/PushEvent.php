<?php

declare(strict_types=1);

namespace App\Incoming\Gitea\Event;

use App\Incoming\Gitea\Input;
use App\Incoming\Gitea\Repository;

class PushEvent extends Input
{
    public function __construct(
        public string $ref,
        public Repository $repository,
    ) {}

    public function isTag(): bool
    {
        return str_starts_with($this->ref, 'refs/tags/');
    }

    public function shortRef(): string
    {
        $parts = explode('/', $this->ref);

        return end($parts);
    }

    public function archiveUrl(): string
    {
        // @todo whitelist
        return "{$this->repository->htmlUrl}/archive/{$this->shortRef()}.zip";
    }
}
