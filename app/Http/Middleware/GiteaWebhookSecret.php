<?php

declare(strict_types=1);

namespace App\Http\Middleware;

class GiteaWebhookSecret extends WebhookSecret
{
    public function __construct()
    {
        parent::__construct(
            secret: config('services.gitea.webhook.secret')
        );
    }
}
