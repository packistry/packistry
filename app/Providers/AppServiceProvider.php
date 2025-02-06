<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\GiteaWebhookSecret;
use App\Http\Middleware\GitHubWebhookSecret;
use App\Http\Middleware\GitlabWebhookSecret;
use App\Models\Token;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->when(GitlabWebhookSecret::class)
            ->needs('$secret')
            ->give(config('services.gitlab.webhook.secret'));

        $this->app->when(GiteaWebhookSecret::class)
            ->needs('$secret')
            ->give(config('services.gitea.webhook.secret'));

        $this->app->when(GitHubWebhookSecret::class)
            ->needs('$secret')
            ->give(config('services.gitea.webhook.secret'));

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict();
        Sanctum::usePersonalAccessTokenModel(Token::class);
        JsonResource::withoutWrapping();
    }
}
