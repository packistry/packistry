<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\InvalidDiscoveryUrlException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Throwable;

#[MapInputName(SnakeCaseMapper::class)]
class OIDCConfiguration extends Data
{
    public function __construct(
        public string $userinfoEndpoint,
        public string $tokenEndpoint,
        public string $authorizationEndpoint,
    ) {
        //
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public static function fromDiscoveryUrl(string $url): self
    {
        $response = Http::acceptJson()
            ->get($url)
            ->throw()
            ->json();

        return self::from($response);
    }

    /**
     * @throws InvalidDiscoveryUrlException
     */
    public static function validateDiscoveryUrl(string $url): void
    {
        try {
            self::fromDiscoveryUrl($url);
        } catch (Throwable) {
            throw new InvalidDiscoveryUrlException;
        }
    }
}
