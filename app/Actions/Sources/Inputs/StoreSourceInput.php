<?php

declare(strict_types=1);

namespace App\Actions\Sources\Inputs;

use App\Actions\Input;
use App\Enums\SourceProvider;
use SensitiveParameter;

class StoreSourceInput extends Input
{
    /**
     * @param  array<string,mixed>  $metadata
     */
    public function __construct(
        public string $name,
        public SourceProvider $provider,
        public string $url,
        #[SensitiveParameter] public string $token,
        public array $metadata = [],
    ) {}
}
