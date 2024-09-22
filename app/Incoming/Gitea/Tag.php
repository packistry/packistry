<?php

declare(strict_types=1);

namespace App\Incoming\Gitea;

use App\Incoming\Importable;

class Tag extends Input implements Importable
{
    public function __construct(
        public string $name,
        public string $message,
        public string $id,
        public string $zipballUrl,
        public Repository $repository,
    ) {}

    public function zipUrl(): string
    {
        return $this->zipballUrl;
    }

    public function version(): string
    {
        return $this->zipballUrl;
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
