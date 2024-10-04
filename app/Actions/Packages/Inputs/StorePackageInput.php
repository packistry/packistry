<?php

declare(strict_types=1);

namespace App\Actions\Packages\Inputs;

use App\Actions\Input;

class StorePackageInput extends Input
{
    /**
     * @param  string[]  $projects
     */
    public function __construct(
        public string $repository,
        public string $source,
        public array $projects,
        public bool $webhook,
    ) {
        //
    }
}
