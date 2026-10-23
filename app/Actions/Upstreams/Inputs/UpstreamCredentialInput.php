<?php

declare(strict_types=1);

namespace App\Actions\Upstreams\Inputs;

use App\Actions\Input;
use App\Enums\UpstreamCredentialType;
use SensitiveParameter;

class UpstreamCredentialInput extends Input
{
    public function __construct(
        public string $host,
        public UpstreamCredentialType $type,
        public ?int $id = null,
        public ?string $username = null,
        #[SensitiveParameter] public ?string $secret = null,
    ) {
        //
    }
}
