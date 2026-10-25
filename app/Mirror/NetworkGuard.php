<?php

declare(strict_types=1);

namespace App\Mirror;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;

/**
 * Upstream URLs, their metadata-url, dist urls and redirects are all controlled by the upstream,
 * so every request is checked against private and reserved addresses (loopback, private ranges,
 * cloud metadata endpoints, ...) unless the host is explicitly allowed.
 */
readonly class NetworkGuard
{
    public function __construct(private HostResolver $resolver)
    {
        //
    }

    /**
     * Guzzle middleware that checks every request, including each redirect hop, and pins the
     * connection to the checked address so DNS cannot change in between.
     */
    public function middleware(): callable
    {
        return fn (callable $handler): callable => function (RequestInterface $request, array $options) use ($handler) {
            $resolve = $this->pinnedAddress($request);

            if ($resolve !== null) {
                $options['curl'][CURLOPT_RESOLVE] = [$resolve];
            }

            return $handler($request, $options);
        };
    }

    /**
     * The curl resolve entry (host:port:address) to connect to, or null for allowed hosts.
     *
     * @throws ConnectException when the host resolves to a private or reserved address
     */
    private function pinnedAddress(RequestInterface $request): ?string
    {
        $uri = $request->getUri();
        $host = Str::lower(trim($uri->getHost(), '[]'));

        if ($this->isAllowedHost($host)) {
            return null;
        }

        $addresses = $this->resolver->resolve($host);

        if ($addresses === []) {
            throw new ConnectException("Could not resolve $host", $request);
        }

        foreach ($addresses as $address) {
            if (! $this->isPublicAddress($address)) {
                throw new ConnectException(
                    "Blocked request to $host ($address): private and reserved addresses are not allowed, "
                        .'add the host to MIRROR_ALLOWED_HOSTS to allow it',
                    $request
                );
            }
        }

        $port = $uri->getPort() ?? ($uri->getScheme() === 'https' ? 443 : 80);
        $address = $addresses[0];

        return "$host:$port:".(str_contains($address, ':') ? "[$address]" : $address);
    }

    private function isAllowedHost(string $host): bool
    {
        $allowed = array_map(
            fn (string $allowed): string => Str::lower(trim($allowed)),
            explode(',', config()->string('mirror.allowed_hosts'))
        );

        return in_array('*', $allowed, true) || in_array($host, $allowed, true);
    }

    private function isPublicAddress(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false) {
            return false;
        }

        // the local-use NAT64 prefix has no fixed layout, so the IPv4 address it reaches is unknown
        if ($this->hasIpv6Prefix($address, '0064ff9b0001')) {
            return false;
        }

        $embedded = $this->embeddedIpv4Address($address);

        return $embedded === null || $this->isPublicAddress($embedded);
    }

    /**
     * NAT64 (64:ff9b::/96) and IPv4-translated (::ffff:0:0:0/96) addresses reach the IPv4 address
     * in their last 32 bits, which may be internal, while filter_var counts them as global.
     */
    private function embeddedIpv4Address(string $address): ?string
    {
        $isTranslated = $this->hasIpv6Prefix($address, '0064ff9b0000000000000000')
            || $this->hasIpv6Prefix($address, '0000000000000000ffff0000');

        if (! $isTranslated) {
            return null;
        }

        return (string) inet_ntop(substr((string) inet_pton($address), 12));
    }

    private function hasIpv6Prefix(string $address, string $hexPrefix): bool
    {
        $packed = inet_pton($address);

        return is_string($packed)
            && strlen($packed) === 16
            && str_starts_with(bin2hex($packed), $hexPrefix);
    }
}
