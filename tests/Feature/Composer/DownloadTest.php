<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\Download;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Octane\RoadRunner\RoadRunnerClient;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

it('downloads a version', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    $path = __DIR__.'/../../Fixtures/project.zip';
    Package::factory()
        ->for($repository)
        ->name('test/test')
        ->has(
            Version::factory()
                ->fromZip($path, '1.0.0', $repository->archivePath(''))
        )
        ->create();

    $response = getJson($repository->url('/test/test/1.0.0'))
        ->assertHeader('Content-Type', 'application/zip')
        ->assertStatus($status);

    $content = $response->streamedContent();

    expect($content)->toBe((string) file_get_contents($path));

    assertDatabaseHas(Download::class, [
        'package_id' => 1,
        'version_id' => 1,
        'token_id' => $auth === null ? null : 1,
        'ip' => '127.0.0.1',
    ]);

    /** @var Package $package */
    $package = Package::query()->first();
    /** @var Version $version */
    $version = Version::query()->first();

    expect($package->total_downloads)->toBe(1)
        ->and($version->total_downloads)->toBe(1);
})
    ->with(rootAndSubRepository(
        public: true
    ))
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ, expiredDeployTokenWithAccessStatus: 200));

it('downloads version from private repository', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    $path = __DIR__.'/../../Fixtures/project.zip';

    Package::factory()
        ->for($repository)
        ->name('test/test')
        ->has(
            Version::factory()
                ->fromZip($path, '1.0.0', $repository->archivePath(''))
        )
        ->create();

    getJson($repository->url('/test/test/1.0.0'))
        ->assertStatus($status);

    if ($status === 200) {
        assertDatabaseHas(Download::class, [
            'package_id' => 1,
            'version_id' => 1,
            'token_id' => 1,
            'ip' => '127.0.0.1',
        ]);

        /** @var Package $package */
        $package = Package::query()->first();
        /** @var Version $version */
        $version = Version::query()->first();

        expect($package->total_downloads)->toBe(1)
            ->and($version->total_downloads)->toBe(1);
    }
})
    ->with(rootAndSubRepository())
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_READ,
        guestStatus: 404,
        personalTokenWithoutAccessStatus: 404,
        deployTokenWithoutAccessStatus: 404,
        deployTokenWithoutPackagesStatus: 404,
    ));

it('streams archives to RoadRunner in chunks under Octane', function (): void {
    $path = __DIR__.'/../../Fixtures/project.zip';
    $repository = repository(public: true);

    Package::factory()
        ->for($repository)
        ->name('test/test')
        ->has(
            Version::factory()
                ->fromZip($path, '1.0.0', $repository->archivePath(''))
        )
        ->create();

    $_SERVER['LARAVEL_OCTANE'] = '1';

    try {
        $response = get($repository->url('/test/test/1.0.0'))->assertOk();
    } finally {
        unset($_SERVER['LARAVEL_OCTANE']);
    }

    /** @var StreamedResponse $streamed */
    $streamed = $response->baseResponse;

    // any other callback makes Octane buffer the whole archive in worker memory
    $callback = RoadRunnerClient::resolveStreamResponseCallback($streamed);

    expect($callback)->not->toBeNull()
        ->and(implode('', iterator_to_array($callback(), false)))->toBe((string) file_get_contents($path));
});
