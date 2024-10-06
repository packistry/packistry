<?php

declare(strict_types=1);

return [
    'proxies' => explode(',', env('TRUSTED_PROXIES', '')),
];
