<?php

declare(strict_types=1);

namespace App\Sources\GitHub\Event;

use App\Normalizer;
use App\Sources\Deletable;
use App\Sources\GitHub\Input;
use App\Sources\GitHub\Repository;

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

    public function url(): string
    {
        return Normalizer::url($this->repository->url);
    }

    public function id(): string
    {
        return (string) $this->repository->id;
    }
}
