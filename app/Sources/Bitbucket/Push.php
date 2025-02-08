<?php

declare(strict_types=1);

namespace App\Sources\Bitbucket;

class Push extends Input
{
    /**
     * @param  Change[]  $changes
     */
    public function __construct(
        public array $changes,
    ) {}
}
