<?php

declare(strict_types=1);

namespace App\Sources\Bitbucket;

class Change extends Input
{
    public function __construct(
        public ?Reference $old = null,
        public ?Reference $new = null,
    ) {}
}
