<?php

declare(strict_types=1);

namespace App;

use App\Models\AuthenticationSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use RuntimeException;

class OIDCProvider extends AbstractProvider implements ProviderInterface
{
    protected AuthenticationSource $source;

    protected OIDCConfiguration $configuration;

    protected $scopeSeparator = ' ';

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public static function forSource(AuthenticationSource $source, Request $request): self
    {
        $provider = new self(
            request: $request,
            clientId: $source->client_id,
            clientSecret: decrypt($source->client_secret),
            redirectUrl: $source->callbackUrl(),
        );

        $provider->source = $source;
        $provider->configuration = OIDCConfiguration::fromDiscoveryUrl($source->discovery_url ?? throw new RuntimeException('Discovery URL is missing'));

        return $provider;
    }

    /**
     * @return string[]
     */
    public function getScopes(): array
    {
        return ['openid', 'profile', 'email'];
    }

    /**
     * @param  string  $state
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            $this->configuration->authorizationEndpoint,
            $state
        );
    }

    protected function getTokenUrl(): string
    {
        return $this->configuration->tokenEndpoint;
    }

    /**
     * @param  string  $token
     * @return array<string, mixed>
     *
     * @throws ConnectionException|RequestException
     */
    protected function getUserByToken($token): array
    {
        return Http::acceptJson()
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])
            ->get($this->configuration->userinfoEndpoint)
            ->throw()
            ->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ConnectionException|RequestException
     */
    public function getAccessTokenResponse($code): array
    {
        return Http::acceptJson()
            ->asForm()
            ->post($this->getTokenUrl(), $this->getTokenFields($code))
            ->throw()
            ->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ConnectionException|RequestException
     */
    protected function getRefreshTokenResponse($refreshToken): array
    {
        return Http::acceptJson()
            ->asForm()
            ->post($this->getTokenUrl(), [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ])
            ->throw()
            ->json();
    }

    /**
     * @param  array<array-key, string>  $user
     *
     * @phpstan-ignore-next-line
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['sub'] ?? throw new RuntimeException('sub not set'),
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? throw new RuntimeException('email not set'),
        ]);
    }
}
