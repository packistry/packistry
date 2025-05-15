<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\FailedToParseUrlException;
use App\Exceptions\VersionNotFoundException;

class Normalizer
{
    /**
     * @throws FailedToParseUrlException
     */
    public static function url(string $url): string
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = "https://$url";
        }

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false || ! array_key_exists('host', $parsedUrl)) {
            throw new FailedToParseUrlException("failed to parse url: $url");
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
        if (str_starts_with($version, 'dev-') || str_ends_with($version, '-dev')) {
            return $version;
        }

        $validPatterns = [
            '/^v?(\d+\.){2,}\d+$/',
            '/^v?(\d+\.){1,}\d+-[a-zA-Z]+\d*$/',
        ];

        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $version)) {
                return trim($version, 'v');
            }
        }

        if (preg_match('/^v?(\d+\.){2,}\d+$/', $version)) {
            return trim($version, 'v');
        }

        throw new VersionNotFoundException;
    }

    public static function devVersion(string $version): string
    {
        if (preg_match('/^v?\d+(\.\w+)*$/', $version) !== 1) {
            return "dev-$version";
        }

        if (str_ends_with($version, '.x')) {
            return $version.'-dev';
        }

        return $version.'.x-dev';
    }
}
