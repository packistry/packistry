<?php

declare(strict_types=1);

namespace App\Sources\Gitea\Event;

use App\Normalizer;
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

        return implode('/', array_slice($parts, 2));
    }

    public function zipUrl(): string
    {
        return "{$this->repository->htmlUrl}/archive/{$this->shortRef()}.zip";
    }

    public function version(): string
    {
        if ($this->isTag()) {
            return $this->shortRef();
        }

        return Normalizer::devVersion($this->shortRef());
    }

    public function url(): string
    {
        return $this->repository->htmlUrl;
    }

    public function id(): string
    {
        return (string) $this->repository->id;
    }
}
