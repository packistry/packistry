<?php

declare(strict_types=1);

namespace App\Sources\Gitlab\Event;

use App\Sources\Deletable;
use App\Sources\Gitea\Input;
use App\Sources\Gitlab\Project;
use App\Sources\Importable;
use RuntimeException;

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

        return end($parts);
    }

    public function zipUrl(): string
    {
        $parsedUrl = parse_url($this->project->webUrl);

        if ($parsedUrl === false || ! array_key_exists('scheme', $parsedUrl) || ! array_key_exists('host', $parsedUrl)) {
            throw new RuntimeException("failed to parse url: {$this->project->webUrl}");
        }

        return "{$parsedUrl['scheme']}://{$parsedUrl['host']}/api/v4/projects/{$this->project->id}/repository/archive.zip?sha=$this->checkoutSha";
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
        return $this->project->pathWithNamespace;
    }

    public function subDirectory(): string
    {
        return "{$this->project->name}-$this->checkoutSha-$this->checkoutSha/";
    }
}
