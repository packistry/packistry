<?php

declare(strict_types=1);

namespace App\Actions\AuthenticationSources;

use App\Actions\AuthenticationSources\Inputs\UpdateAuthenticationSourceInput;
use App\Enums\AuthenticationProvider;
use App\Exceptions\InvalidDiscoveryUrlException;
use App\Models\AuthenticationSource;
use App\OIDCConfiguration;
use DB;
use Throwable;

class UpdateAuthenticationSource
{
    /**
     * @throws InvalidDiscoveryUrlException|Throwable
     */
    public function handle(AuthenticationSource $source, UpdateAuthenticationSourceInput $input): AuthenticationSource
    {
        if ($source->discovery_url !== $input->discoveryUrl && $input->provider === AuthenticationProvider::OIDC) {
            OIDCConfiguration::validateDiscoveryUrl($input->discoveryUrl ?? throw new InvalidDiscoveryUrlException);
        }

        return DB::transaction(function () use ($source, $input) {
            $source->name = $input->name;
            $source->provider = $input->provider;
            $source->icon_url = $input->iconUrl;
            $source->client_id = $input->clientId;
            $source->client_secret = $input->clientSecret;
            $source->discovery_url = $input->discoveryUrl;

            $source->active = $input->active;
            $source->default_user_role = $input->defaultUserRole;

            $source->allow_registration = $input->allowRegistration;
            $source->allowed_domains = array_values(array_unique(array_map('strtolower', $input->allowedDomains ?? [])));

            $source->save();

            $source->repositories()->sync($input->defaultUserRepositories ?? []);
            $source->packages()->sync($input->defaultUserPackages ?? []);

            return $source;
        });
    }
}
