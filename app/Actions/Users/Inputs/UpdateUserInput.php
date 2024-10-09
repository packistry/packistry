<?php

declare(strict_types=1);

namespace App\Actions\Users\Inputs;

use App\Actions\Input;
use App\Enums\Role;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Optional;

class UpdateUserInput extends Input
{
    /**
     * @param  string[]|Optional  $repositories
     */
    public function __construct(
        public string|Optional $name,
        #[Email]
        public string|Optional $email,
        public Role|Optional $role,
        public array|Optional $repositories,
        #[Password(6)]
        public ?string $password = null,
    ) {
        //
    }
}
