<?php

declare(strict_types=1);

namespace Tests;

use App\Mirror\HostResolver;

/**
 * Resolves every host to a public address unless mapped otherwise, so tests never do real DNS lookups.
 */
class FakeHostResolver implements HostResolver
{
    /**
     * @var array<string, string[]>
     */
    private array $map = [];

    public function map(string $host, string ...$addresses): void
    {
        $this->map[strtolower($host)] = $addresses;
    }

    public function resolve(string $host): array
    {
        $host = trim(strtolower($host), '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        return $this->map[$host] ?? ['93.184.216.34'];
    }
}
