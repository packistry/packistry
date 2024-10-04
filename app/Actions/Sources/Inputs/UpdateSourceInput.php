<?php

declare(strict_types=1);

namespace App\Actions\Sources\Inputs;

use App\Actions\Input;
use App\Enums\SourceProvider;
use SensitiveParameter;
use Spatie\LaravelData\Optional;

class UpdateSourceInput extends Input
{
    public function __construct(
        public string $name,
        public SourceProvider $provider,
        public string $url,
        #[SensitiveParameter] public Optional|string $token,
    ) {}
}
