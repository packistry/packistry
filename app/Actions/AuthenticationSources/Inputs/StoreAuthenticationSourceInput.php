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
        public ?array $allowedDomains = [],
        public bool $allowRegistration = false
    ) {
        //
    }
}
