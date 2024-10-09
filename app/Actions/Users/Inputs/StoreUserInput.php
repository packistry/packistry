<?php

declare(strict_types=1);

namespace App\Actions\Users\Inputs;

use App\Actions\Input;
use App\Enums\Role;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Password;

class StoreUserInput extends Input
{
    /**
     * @param  string[]  $repositories
     */
    public function __construct(
        public string $name,
        #[Email]
        public string $email,
        public Role $role,
        #[Password(6)]
        public string $password,
        public ?array $repositories = [],
    ) {
        //
    }
}
