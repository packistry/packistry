<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\VersionNotFoundException;
use RuntimeException;

class Normalizer
{
    /**
     * @throws RuntimeException
     */
    public static function url(string $url): string
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = "https://$url";
        }

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false || ! array_key_exists('host', $parsedUrl)) {
            throw new RuntimeException("failed to parse url: $url");
        }

        $scheme = array_key_exists('scheme', $parsedUrl) ? $parsedUrl['scheme'] : 'https';
        $isHttpPort = ! array_key_exists('port', $parsedUrl) || $parsedUrl['port'] === 80 || $parsedUrl['port'] === 443;

        $port = $isHttpPort ? '' : ":{$parsedUrl['port']}";

        return "$scheme://{$parsedUrl['host']}$port";
    }

    /**
     * @throws VersionNotFoundException
     */
    public static function version(string $version): string
    {
        if (! str_starts_with($version, 'dev-')) {
            $result = preg_match('/\d+\.\d+\.\d+/', $version, $matches);

            if ($result === 0 || $result === false) {
                throw new VersionNotFoundException;
            }

            return $matches[0];
        }

        return $version;
    }
}
