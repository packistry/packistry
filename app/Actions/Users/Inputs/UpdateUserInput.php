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
     * @param  string[]|Optional  $packages
     */
    public function __construct(
        public string|Optional $name,
        #[Email]
        public string|Optional $email,
        public Role|Optional $role = new Optional,
        public array|Optional $repositories = new Optional,
        public array|Optional $packages = new Optional,
        #[Password(6)]
        public ?string $password = null,
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
