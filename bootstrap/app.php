<?php

declare(strict_types=1);

use App\HasValidationMessage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
        apiPrefix: '',
        then: function (): void {
            Route::middleware('web')
                ->get('{any?}', fn () => response()->file(public_path('index.html')))->where('any', '.*');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Exception $exception): void {
            if ($exception instanceof HasValidationMessage) {
                throw $exception::asValidationMessage();
            }
        });
    })->create();
