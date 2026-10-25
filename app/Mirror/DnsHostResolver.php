<?php

declare(strict_types=1);

namespace App\Mirror;

class DnsHostResolver implements HostResolver
{
    public function resolve(string $host): array
    {
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        // uses the system resolver, so /etc/hosts entries are included
        $ipv4 = gethostbynamel($host);

        $ipv6 = @dns_get_record($host, DNS_AAAA);
        $ipv6 = is_array($ipv6) ? array_column($ipv6, 'ipv6') : [];

        return array_values(array_unique([
            ...($ipv4 === false ? [] : $ipv4),
            ...$ipv6,
        ]));
    }
}
