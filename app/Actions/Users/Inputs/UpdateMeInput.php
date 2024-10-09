<?php

declare(strict_types=1);

namespace App\Actions\Users\Inputs;

use App\Actions\Input;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;

class UpdateMeInput extends Input
{
    public function __construct(
        public string $name,
        #[RequiredWith('password_confirmation', 'password')]
        public ?string $currentPassword = null,
        #[Password(6), Confirmed, RequiredWith('password_confirmation', 'current_password')]
        public ?string $password = null,
        #[RequiredWith('current_password', 'password')]
        public ?string $passwordConfirmation = null,
    ) {
        //
    }
}
