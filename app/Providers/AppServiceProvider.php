<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\GiteaWebhookSecret;
use App\Http\Middleware\GitlabWebhookSecret;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->when(GitlabWebhookSecret::class)
            ->needs('$secret')
            ->give(config('services.gitlab.webhook.secret'));

        $this->app->when(GiteaWebhookSecret::class)
            ->needs('$secret')
            ->give(config('services.gitea.webhook.secret'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
