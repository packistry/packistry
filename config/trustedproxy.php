<?php

declare(strict_types=1);

$proxies = (string) env('TRUSTED_PROXIES', '');

return [
    'proxies' => in_array($proxies, ['*', '**', '']) ? '*' : explode(',', $proxies),
];
