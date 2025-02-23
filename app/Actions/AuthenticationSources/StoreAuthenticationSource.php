<?php

declare(strict_types=1);

namespace App\Actions\AuthenticationSources;

use App\Actions\AuthenticationSources\Inputs\StoreAuthenticationSourceInput;
use App\Enums\AuthenticationProvider;
use App\Exceptions\InvalidDiscoveryUrlException;
use App\Models\AuthenticationSource;
use App\OIDCConfiguration;
use DB;
use Throwable;

class StoreAuthenticationSource
{
    /**
     * @throws InvalidDiscoveryUrlException|Throwable
     */
    public function handle(StoreAuthenticationSourceInput $input): AuthenticationSource
    {
        if ($input->provider === AuthenticationProvider::OIDC) {
            OIDCConfiguration::validateDiscoveryUrl($input->discoveryUrl ?? throw new InvalidDiscoveryUrlException);
        }

        return DB::transaction(function () use ($input) {
            $source = new AuthenticationSource;

            $source->name = $input->name;
            $source->provider = $input->provider;
            $source->icon_url = $input->iconUrl;
            $source->client_id = $input->clientId;
            $source->client_secret = $input->clientSecret;
            $source->discovery_url = $input->discoveryUrl;

            $source->active = $input->active;
            $source->default_user_role = $input->defaultUserRole;

            $source->save();

            $source->repositories()->sync($input->defaultUserRepositories ?? []);

            return $source;
        });
    }
}
