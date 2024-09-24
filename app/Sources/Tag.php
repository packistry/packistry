<?php

declare(strict_types=1);

namespace App\Sources;

readonly class Tag implements Importable
{
    public function __construct(
        private string $name,
        private string $projectFullName,
        private string $zipUrl,
        private string $subDirectory,
    ) {
        //
    }

    public function zipUrl(): string
    {
        return $this->zipUrl;
    }

    public function version(): string
    {
        return $this->name;
    }

    public function name(): string
    {
        return $this->projectFullName;
    }

    public function subDirectory(): string
    {
        return $this->subDirectory;
    }
}
