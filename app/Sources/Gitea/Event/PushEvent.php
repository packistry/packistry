<?php

declare(strict_types=1);

namespace App\Sources\Gitea\Event;

use App\Sources\Gitea\Input;
use App\Sources\Gitea\Repository;
use App\Sources\Importable;

class PushEvent extends Input implements Importable
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

    public function zipUrl(): string
    {
        // @todo whitelist
        return "{$this->repository->htmlUrl}/archive/{$this->shortRef()}.zip";
    }

    public function version(): string
    {
        if ($this->isTag()) {
            return $this->shortRef();
        }

        return "dev-{$this->shortRef()}";
    }

    public function name(): string
    {
        return $this->repository->fullName;
    }

    public function subDirectory(): string
    {
        return "{$this->repository->name}/";
    }
}
