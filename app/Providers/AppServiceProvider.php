<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Token;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
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
