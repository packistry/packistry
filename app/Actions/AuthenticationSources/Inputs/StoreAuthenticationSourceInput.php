<?php

declare(strict_types=1);

namespace App\Actions\AuthenticationSources\Inputs;

use App\Actions\Input;
use App\Enums\AuthenticationProvider;
use App\Enums\Role;
use Spatie\LaravelData\Attributes\Validation\Url;

class StoreAuthenticationSourceInput extends Input
{
    /**
     * @param  string[]  $defaultUserRepositories
     * @param  string[]  $defaultUserPackages
     * @param  string[]  $allowedDomains
     */
    public function __construct(
        public string $name,
        #[Url]
        public ?string $iconUrl,
        public AuthenticationProvider $provider,
        public string $clientId,
        public string $clientSecret,
        public ?string $discoveryUrl = '',
        public bool $active = true,
        public Role $defaultUserRole = Role::USER,
        public ?array $defaultUserRepositories = [],
        public ?array $defaultUserPackages = [],
        public ?array $allowedDomains = [],
        public bool $allowRegistration = false
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'default_user_repositories' => ['array'],
            'default_user_repositories.*' => ['integer', 'exists:repositories,id'],
            'default_user_packages' => ['array'],
            'default_user_packages.*' => ['integer', 'exists:packages,id'],
        ];
    }
}
