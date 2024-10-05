<?php

declare(strict_types=1);

use App\Sources\Gitea\GiteaClient;
use App\Sources\GitHub\GitHubClient;
use App\Sources\Gitlab\GitlabClient;

return [
    'github' => [
        'client' => GitHubClient::class,
    ],

    'gitea' => [
        'client' => GiteaClient::class,
    ],

    'gitlab' => [
        'client' => GitlabClient::class,
    ],
];
