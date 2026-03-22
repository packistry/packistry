<?php

declare(strict_types=1);

namespace App\Actions\Repositories\Inputs;

use App\Actions\Input;
use App\Enums\RepositorySyncMode;

class StoreRepositoryInput extends Input
{
    public function __construct(
        public string $name,
        public ?string $path = null,
        public ?string $description = null,
        public bool $public = false,
        public RepositorySyncMode $syncMode = RepositorySyncMode::SOURCE,
    ) {
        //
    }
}
