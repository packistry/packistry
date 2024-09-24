<?php

declare(strict_types=1);

namespace App\Sources;

readonly class Branch implements Importable
{
    public function __construct(
        private string $name,
        private string $projectFullName,
        private string $zipUrl,
    ) {
        //
    }

    public function zipUrl(): string
    {
        return $this->zipUrl;
    }

    public function version(): string
    {
        return "dev-$this->name";
    }

    public function name(): string
    {
        return $this->projectFullName;
    }
}
