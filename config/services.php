<?php

declare(strict_types=1);

return [
    'gitea' => [
        'webhook' => [
            'secret' => env('GITEA_WEBHOOK_SECRET'),
        ],
    ],

    'gitlab' => [
        'webhook' => [
            'secret' => env('GITEA_WEBHOOK_SECRET'),
        ],
    ],
];
