<?php

declare(strict_types=1);

namespace App\Sources\Bitbucket;

class Link extends Input
{
    public function __construct(
        public string $href,
    ) {}
}
