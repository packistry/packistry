<?php

declare(strict_types=1);

use App\Sources\Gitea\GiteaClient;
use App\Sources\Gitlab\GitlabClient;

return [
    'gitea' => [
        'client' => GiteaClient::class,
        'webhook' => [
            'secret' => env('GITEA_WEBHOOK_SECRET'),
        ],
    ],

    'gitlab' => [
        'client' => GitlabClient::class,
        'webhook' => [
            'secret' => env('GITLAB_WEBHOOK_SECRET'),
        ],
    ],
];
