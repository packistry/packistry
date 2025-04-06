<?php

declare(strict_types=1);

namespace App\Sources;

use App\Normalizer;

readonly class Branch implements Importable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $url,
        public string $zipUrl,
    ) {
        //
    }

    public function zipUrl(): string
    {
        return $this->zipUrl;
    }

    public function version(): string
    {
        return Normalizer::devVersion($this->name);
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
