<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Upstream mirroring
    |--------------------------------------------------------------------------
    |
    | Timings (in seconds) used when Packistry proxies and caches packages
    | from upstream Composer registries such as Packagist or GitLab.
    |
    */

    // how long cached package metadata is served before revalidating upstream
    'metadata_ttl' => (int) env('MIRROR_METADATA_TTL', 300),

    // how long an upstream's discovered metadata-url is trusted
    'root_ttl' => (int) env('MIRROR_ROOT_TTL', 86400),

    // how long an upstream "package not found" answer is cached
    'not_found_ttl' => (int) env('MIRROR_NOT_FOUND_TTL', 600),

    // how long an upstream is skipped after it failed to respond
    'down_ttl' => (int) env('MIRROR_DOWN_TTL', 60),

    'connect_timeout' => (int) env('MIRROR_CONNECT_TIMEOUT', 5),
    'timeout' => (int) env('MIRROR_TIMEOUT', 15),
    'download_timeout' => (int) env('MIRROR_DOWNLOAD_TIMEOUT', 300),

    // full: record every download, counts: only increment counters, off: record nothing
    'download_stats' => env('MIRROR_DOWNLOAD_STATS', 'full'),

];
