<?php

declare(strict_types=1);

namespace App\Mirror;

use App\Exceptions\UnsupportedUpstreamException;
use App\Exceptions\UpstreamUnavailableException;
use App\Models\Upstream;
use App\Models\UpstreamCredential;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;

class UpstreamClient
{
    /**
     * Credentials are attached per request based on the request host, so they are
     * also evaluated for every redirect hop and never leak to other hosts.
     */
    public function request(Upstream $upstream, ?int $timeout = null): PendingRequest
    {
        $credentials = $upstream->credentials->keyBy(
            fn (UpstreamCredential $credential): string => Str::lower($credential->host)
        );

        return Http::withUserAgent('Packistry ('.config('app.url').')')
            ->connectTimeout(config()->integer('mirror.connect_timeout'))
            ->timeout($timeout ?? config()->integer('mirror.timeout'))
            ->withRequestMiddleware(function (RequestInterface $request) use ($credentials): RequestInterface {
                /** @var UpstreamCredential|null $credential */
                $credential = $credentials->get(Str::lower($request->getUri()->getHost()));

                if ($credential === null) {
                    return $request;
                }

                foreach ($credential->headers() as $header => $value) {
                    $request = $request->withHeader($header, $value);
                }

                return $request;
            });
    }

    /**
     * Discover (and cache) the Composer 2 metadata-url of the upstream.
     *
     * @throws UpstreamUnavailableException
     * @throws UnsupportedUpstreamException
     */
    public function metadataUrl(Upstream $upstream, bool $force = false): string
    {
        $ttl = config()->integer('mirror.root_ttl');

        if (
            ! $force
            && $upstream->metadata_url !== null
            && $upstream->root_checked_at?->gt(now()->subSeconds($ttl)) === true
        ) {
            return $upstream->metadata_url;
        }

        $root = $this->rootUrl($upstream);

        try {
            $response = $this->request($upstream)->acceptJson()->get($root);
        } catch (ConnectionException $e) {
            return $this->fallbackMetadataUrl($upstream, $force, $e->getMessage());
        }

        if (! $response->successful()) {
            return $this->fallbackMetadataUrl($upstream, $force, "Fetching $root failed with HTTP {$response->status()}");
        }

        $metadataUrl = $response->json('metadata-url');

        if (! is_string($metadataUrl) || ! str_contains($metadataUrl, '%package%')) {
            $this->recordError($upstream, $message = "$root does not provide a Composer 2 metadata-url");

            throw new UnsupportedUpstreamException($message);
        }

        $upstream->metadata_url = self::resolveUrl($root, $metadataUrl);
        $upstream->root_checked_at = now();
        $upstream->last_error = null;
        $upstream->last_error_at = null;
        $upstream->save();

        return $upstream->metadata_url;
    }

    public function rootUrl(Upstream $upstream): string
    {
        $url = rtrim($upstream->url, '/');

        return Str::endsWith($url, '.json') ? $url : "$url/packages.json";
    }

    public function isDown(Upstream $upstream): bool
    {
        return Cache::has("mirror:down:$upstream->id");
    }

    /**
     * Skip the upstream for a short while, so an outage does not make every request wait for a timeout.
     */
    public function markDown(Upstream $upstream, string $error): void
    {
        Cache::put("mirror:down:$upstream->id", true, config()->integer('mirror.down_ttl'));

        $this->recordError($upstream, $error);
    }

    /**
     * Clear the last error once the upstream answers again.
     */
    public function recordSuccess(Upstream $upstream): void
    {
        if ($upstream->last_error === null) {
            return;
        }

        $upstream->last_error = null;
        $upstream->last_error_at = null;
        $upstream->saveQuietly();
    }

    public function recordError(Upstream $upstream, string $error): void
    {
        $upstream->last_error = Str::limit($error, 1000);
        $upstream->last_error_at = now();
        $upstream->saveQuietly();
    }

    public static function resolveUrl(string $base, string $url): string
    {
        if (preg_match('{^[a-z][a-z0-9+.-]*://}i', $url) === 1) {
            return $url;
        }

        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';

        if (str_starts_with($url, '//')) {
            return "$scheme:$url";
        }

        $origin = $scheme.'://'.($parts['host'] ?? '').(isset($parts['port']) ? ":{$parts['port']}" : '');

        if (str_starts_with($url, '/')) {
            return $origin.$url;
        }

        $path = $parts['path'] ?? '/';
        $directory = substr($path, 0, (int) strrpos($path, '/') + 1);

        return $origin.($directory === '' ? '/' : $directory).$url;
    }

    /**
     * @throws UpstreamUnavailableException
     */
    private function fallbackMetadataUrl(Upstream $upstream, bool $force, string $error): string
    {
        $this->markDown($upstream, $error);

        if (! $force && $upstream->metadata_url !== null) {
            return $upstream->metadata_url;
        }

        throw new UpstreamUnavailableException($error);
    }
}
