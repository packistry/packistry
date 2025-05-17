<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\FailedToParseUrlException;
use App\Exceptions\VersionNotFoundException;
use Composer\Semver\VersionParser;

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

        if (preg_match('/^v?(\d+\.){1,}\d+(-[a-zA-Z]+\d*)?$/', $version)) {
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

    public static function versionOrder(string $version): string
    {
        if (str_starts_with($version, 'dev-')) {
            return $version;
        }

        $parser = new VersionParser;
        $normalized = $parser->normalize($version);

        [$numericId, $buildId] = str_contains($normalized, '-')
            ? explode('-', $normalized)
            : [$normalized, null];

        $numericVersion = str($numericId)
            ->explode('.')
            ->map(fn (string $number) => str_pad($number, 7, '0', STR_PAD_LEFT))
            ->join('.');

        if (is_null($buildId)) {
            return "{$numericVersion}~";
        }

        $buildVersion = str($buildId)
            ->replaceMatches('/^([a-zA-Z]+)([0-9]*)$/', '$1.$2', 1)
            ->explode('.')
            ->map(fn (string $part, int $index) => match ($index) {
                0 => strtolower($part),
                default => str_pad($part, 3, '0', STR_PAD_LEFT),
            })
            ->join('.');

        return "{$numericVersion}-{$buildVersion}";
    }
}
