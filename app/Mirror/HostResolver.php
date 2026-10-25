<?php

declare(strict_types=1);

namespace App\Mirror;

interface HostResolver
{
    /**
     * The IP addresses a host name resolves to, or the address itself for an IP literal.
     *
     * @return string[]
     */
    public function resolve(string $host): array;
}
