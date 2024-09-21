<?php

declare(strict_types=1);

use App\Http\Controllers\ComposerRepositoryController;
use Illuminate\Support\Facades\Route;


if (! function_exists('composerRoutes')) {
    function composerRoutes(): void
    {
        Route::get('/packages.json', [ComposerRepositoryController::class, 'packages']);
        Route::get('/search.json', [ComposerRepositoryController::class, 'search']);
        Route::get('/list.json', [ComposerRepositoryController::class, 'list']);
        Route::get('/p2/{vendor}/{name}~dev.json', [ComposerRepositoryController::class, 'packageDev']);
        Route::get('/p2/{vendor}/{name}.json', [ComposerRepositoryController::class, 'package']);
        Route::post('/{vendor}/{name}', [ComposerRepositoryController::class, 'upload']);
        Route::get('/{vendor}/{name}/{version}', [ComposerRepositoryController::class, 'download']);
    }
}

Route::prefix('/{repository}')->group(function (): void {
    composerRoutes();
});

composerRoutes();
