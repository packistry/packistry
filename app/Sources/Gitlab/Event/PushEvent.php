<?php

declare(strict_types=1);

namespace App\Sources\Gitlab\Event;

use App\Normalizer;
use App\Sources\Deletable;
use App\Sources\Gitlab\Input;
use App\Sources\Gitlab\Project;
use App\Sources\Importable;

class PushEvent extends Input implements Deletable, Importable
{
    public function __construct(
        public string $ref,
        public string $after,
        public string $before,
        public ?string $checkoutSha,
        public Project $project,
    ) {}

    public function isDelete(): bool
    {
        return $this->checkoutSha === null && $this->after === '0000000000000000000000000000000000000000';
    }

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
        return "{$this->url()}/api/v4/projects/{$this->project->id}/repository/archive.zip?sha=$this->checkoutSha";
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
        return Normalizer::url($this->project->webUrl);
    }

    public function id(): string
    {
        return (string) $this->project->id;
    }
}
