<?php

declare(strict_types=1);

use App\Http\Controllers\ComposerRepositoryController;
use Illuminate\Support\Facades\Route;

function routes(): void
{
    Route::get('/packages.json', [ComposerRepositoryController::class, 'packages']);
    Route::get('/search.json', [ComposerRepositoryController::class, 'search']);
    Route::get('/list.json', [ComposerRepositoryController::class, 'list']);
    Route::get('/p2/{vendor}/{name}~dev.json', [ComposerRepositoryController::class, 'packageDev']);
    Route::get('/p2/{vendor}/{name}.json', [ComposerRepositoryController::class, 'package']);
    Route::get('/files/{name}/{version}/{filename}', [ComposerRepositoryController::class, 'download']);
    Route::put('/', [ComposerRepositoryController::class, 'upload']);
}

Route::prefix('/{repository}')->group(function () {
    routes();
});

routes();
