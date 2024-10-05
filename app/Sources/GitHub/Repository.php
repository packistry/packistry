<?php

declare(strict_types=1);

namespace App\Sources\GitHub;

class Repository extends Input
{
    public function __construct(
        public int $id,
        public string $name,
        public string $fullName,
        public string $htmlUrl,
        public string $url,
    ) {}
}
