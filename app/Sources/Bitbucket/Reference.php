<?php

declare(strict_types=1);

namespace App\Sources\Bitbucket;

class Reference extends Input
{
    public function __construct(
        public string $name,
        public string $type,
    ) {}
}
