<?php

declare(strict_types=1);

namespace App\Actions\Sources\Inputs;

use App\Actions\Input;
use SensitiveParameter;
use Spatie\LaravelData\Optional;

class UpdateSourceInput extends Input
{
    public function __construct(
        public string $name,
        public string $url,
        #[SensitiveParameter] public Optional|string $token,
        public bool $use_name_as_workspace,
    ) {}
}
