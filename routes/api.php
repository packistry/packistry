<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthenticationSourceController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\Composer;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeployTokenController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PersonalTokenController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\Webhook;
use App\Http\Middleware\AcceptsJsonOrRedirectApp;
use App\Http\Middleware\ForceJson;

if (! function_exists('repositoryRoutes')) {
    function repositoryRoutes(): void
    {
        Route::prefix('/incoming')->middleware(ForceJson::class)->group(function (): void {
            Route::post('/gitea/{sourceId}', Webhook\GiteaController::class);
            Route::post('/github/{sourceId}', Webhook\GitHubController::class);
            Route::post('/gitlab/{sourceId}', Webhook\GitlabController::class);
            Route::post('/bitbucket/{sourceId}', Webhook\BitbucketController::class);
        });

        Route::get('/packages.json', [Composer\RepositoryController::class, 'packages']);
        Route::get('/search.json', [Composer\RepositoryController::class, 'search']);
        Route::get('/list.json', [Composer\RepositoryController::class, 'list']);
        Route::get('/p2/{vendor}/{name}~dev.json', [Composer\RepositoryController::class, 'packageDev']);
        Route::get('/p2/{vendor}/{name}.json', [Composer\RepositoryController::class, 'package']);
        Route::post('/{vendor}/{name}', [Composer\RepositoryController::class, 'upload']);
        Route::get('/{vendor}/{name}/{version}', [Composer\RepositoryController::class, 'download'])
            ->where('version', '.*');
    }
}

Route::middleware(['web'])->group(function (): void {
    Route::get('/auths', [AuthController::class, 'sources']);
    Route::get('/auths/{authenticationSourceId}/redirect', [AuthController::class, 'redirect']);
    Route::get('/auths/{authenticationSourceId}/callback', [AuthController::class, 'callback']);
});

Route::middleware(['web', AcceptsJsonOrRedirectApp::class])->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/dashboard', DashboardController::class);
        Route::apiResource('/personal-tokens', PersonalTokenController::class)
            ->only(['index', 'store', 'destroy']);

        Route::apiResource('/deploy-tokens', DeployTokenController::class)
            ->only(['index', 'store', 'destroy']);

        Route::apiResource('/users', UserController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('/sources/{source}/projects', [SourceController::class, 'projects']);
        Route::apiResource('/sources', SourceController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::apiResource('/repositories', RepositoryController::class)
            ->only(['index', 'store', 'destroy', 'update']);

        Route::apiResource('/packages', PackageController::class)
            ->only(['index', 'store', 'destroy', 'show']);

        Route::apiResource('/packages/{packageId}/versions', VersionController::class)
            ->only(['index']);

        Route::get('/packages/{packageId}/downloads', [PackageController::class, 'downloads']);

        Route::apiResource('/authentication-sources', AuthenticationSourceController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'show']);
        Route::patch('/me', [AuthController::class, 'update']);

        Route::get('/batches', [BatchController::class, 'index']);
        Route::delete('/batches', [BatchController::class, 'destroy']);
    });
});

Route::prefix('/r/{repository}')->group(function (): void {
    repositoryRoutes();
});

repositoryRoutes();
