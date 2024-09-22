<?php

declare(strict_types=1);

namespace App\Incoming\Gitea;

use App\Incoming\Importable;

class Branch extends Input implements Importable
{
    public function __construct(
        public string $name,
        public Repository $repository,
    ) {}

    public function zipUrl(): string
    {
        return "{$this->repository->htmlUrl}/archive/$this->name.zip";
    }

    public function version(): string
    {
        return "dev-$this->name";
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
