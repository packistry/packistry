<?php

declare(strict_types=1);

namespace App\Sources\Bitbucket;

class Repository extends Input
{
    public function __construct(
        public string $name,
        public string $fullName,
        public string $uuid,
        public Links $links,
    ) {}
}
