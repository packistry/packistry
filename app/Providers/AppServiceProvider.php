<?php

declare(strict_types=1);

namespace App\Providers;

use App\Mirror\DnsHostResolver;
use App\Mirror\HostResolver;
use App\Models\Token;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        HostResolver::class => DnsHostResolver::class,
    ];

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
