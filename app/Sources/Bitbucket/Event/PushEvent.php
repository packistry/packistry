<?php

declare(strict_types=1);

namespace App\Sources\Bitbucket\Event;

use App\Normalizer;
use App\Sources\Bitbucket\Change;
use App\Sources\Bitbucket\Input;
use App\Sources\Bitbucket\Push;
use App\Sources\Bitbucket\Reference;
use App\Sources\Bitbucket\Repository;
use App\Sources\Deletable;
use App\Sources\Importable;
use RuntimeException;

class PushEvent extends Input implements Deletable, Importable
{
    public function __construct(
        public Push $push,
        public Repository $repository,
    ) {}

    public function latestChange(): Change
    {
        return $this->push->changes[0] ?? throw new RuntimeException('No changes supplied in webhook');
    }

    public function isDelete(): bool
    {
        return $this->latestChange()->new === null;
    }

    public function latestReference(): Reference
    {
        $reference = $this->isDelete()
            ? $this->latestChange()->old
            : $this->latestChange()->new;

        if ($reference === null) {
            throw new RuntimeException('Neither old or new has been provided');
        }

        return $reference;
    }

    public function isTag(): bool
    {
        return $this->latestReference()->type === 'tag';
    }

    public function shortRef(): string
    {
        return $this->latestReference()->name;
    }

    public function zipUrl(): string
    {
        return "{$this->url()}/get/{$this->shortRef()}.zip";
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
        return $this->repository->links->html->href;
    }

    public function sourceUrl(): string
    {
        return $this->repository->links->html->href;
    }

    public function id(): string
    {
        return trim($this->repository->uuid, '{}');
    }

    public function reference(): string
    {
        return $this->shortRef();
    }
}
