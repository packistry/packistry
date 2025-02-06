<?php

declare(strict_types=1);

return [
    'proxies' => explode(',', (string) env('TRUSTED_PROXIES', '')),
];
