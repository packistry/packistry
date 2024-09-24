<?php

declare(strict_types=1);

namespace App\Sources;

readonly class Tag implements Importable
{
    public function __construct(
        private string $id,
        private string $name,
        private string $url,
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
        return $this->name;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function id(): string
    {
        return $this->id;
    }
}
