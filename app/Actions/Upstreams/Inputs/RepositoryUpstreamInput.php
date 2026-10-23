<?php

declare(strict_types=1);

namespace App\Actions\Upstreams\Inputs;

use App\Actions\Input;

class RepositoryUpstreamInput extends Input
{
    public function __construct(
        public int $id,
        public bool $enabled = true,
    ) {
        //
    }
}
