<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\Download;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Illuminate\Contracts\Auth\Authenticatable;

use function Pest\Laravel\assertDatabaseHas;
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

    getJson($repository->url('/test/test/1.0.0'))
        ->assertStatus($status)
        ->assertContent((string) file_get_contents($path));

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
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ));

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

    if ($status !== 401) {
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
        guestStatus: 401,
        personalTokenWithoutAccessStatus: 401,
        deployTokenWithoutAccessStatus: 401,
    ));
