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
     * @param  string[]  $packages
     */
    public function __construct(
        public string $name,
        #[Email]
        public string $email,
        public Role $role,
        #[Password(6)]
        public string $password,
        public ?array $repositories = [],
        public ?array $packages = [],
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'repositories' => ['array'],
            'repositories.*' => ['integer', 'exists:repositories,id'],
            'packages' => ['array'],
            'packages.*' => ['integer', 'exists:packages,id'],
        ];
    }
}
