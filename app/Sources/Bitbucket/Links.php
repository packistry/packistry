<?php

declare(strict_types=1);

namespace App\Sources\Bitbucket;

class Links extends Input
{
    public function __construct(
        public Link $html,
        public Link $self,
    ) {}
}
