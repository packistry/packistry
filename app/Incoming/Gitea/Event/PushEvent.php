<?php

declare(strict_types=1);

namespace App\Incoming\Gitea\Event;

use App\Incoming\Gitea\Commit;
use App\Incoming\Gitea\Input;
use App\Incoming\Gitea\Repository;
use App\Incoming\Gitea\User;

class PushEvent extends Input
{
    public function __construct(
        public string $ref,
        public string $before,
        public string $after,
        public Commit $headCommit,
        public Repository $repository,
        public User $pusher,
        public User $sender
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
