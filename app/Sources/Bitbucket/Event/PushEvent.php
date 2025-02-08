<?php

declare(strict_types=1);

namespace App\Sources\Bitbucket\Event;

use App\Sources\Bitbucket\Input;
use App\Sources\Bitbucket\Repository;
use App\Sources\Deletable;
use App\Sources\Importable;
use Spatie\LaravelData\Attributes\MapInputName;

class PushEvent extends Input implements Deletable, Importable
{
    public function __construct(
        #[MapInputName('push.changes.0.new.name')]
        public ?string $ref,
        #[MapInputName('push.changes.0.new.type')]
        public ?string $type,
        #[MapInputName('push.changes.0.old.name')]
        public ?string $oldRef,
        #[MapInputName('push.changes.0.old.type')]
        public ?string $oldType,
        public Repository $repository,
    ) {}

    public function isDelete(): bool
    {
        return $this->ref === null;
    }

    public function isTag(): bool
    {
        if ($this->isDelete()) {
            return $this->oldType === 'tag';
        }

        return $this->type === 'tag';
    }

    public function shortRef(): string
    {
        $ref = $this->isDelete()
            ? $this->oldRef
            : $this->ref;

        if ($ref === null) {
            throw new \RuntimeException('Neither old or new ref supplied');
        }

        return $ref;
    }

    public function zipUrl(): string
    {
        return "{$this->repository->htmlUrl}/get/{$this->shortRef()}.zip";
    }

    public function version(): string
    {
        return $this->isTag()
            ? str_replace('v', '', $this->shortRef()) // ?
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
