<?php

declare(strict_types=1);

namespace App\Sources\Bitbucket\Event;

use App\Sources\Bitbucket\Input;
use App\Sources\Bitbucket\Repository;
use App\Sources\Importable;
use Spatie\LaravelData\Attributes\MapInputName;

class PushEvent extends Input implements Importable
{
    public function __construct(
        #[MapInputName('push.changes.0.new.name')]
        public string $ref,

        #[MapInputName('push.changes.0.new.type')]
        public string $type,

        public Repository $repository,
    ) {}

    public function isTag(): bool
    {
        return $this->type === 'tag';
    }

    public function shortRef(): string
    {
        return $this->ref;
    }

    public function zipUrl(): string
    {
        return "{$this->repository->htmlUrl}/get/{$this->shortRef()}.zip";
    }

    public function version(): string
    {
        return $this->isTag()
            ? $this->shortRef()
            : "dev-{$this->shortRef()}";
    }

    public function url(): string
    {
        return $this->repository->htmlUrl;
    }

    public function id(): string
    {
        return $this->repository->id;
    }
}
