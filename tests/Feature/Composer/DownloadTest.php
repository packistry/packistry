<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Models\Download;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use App\Models\Version;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;

it('downloads a version', function (Repository $repository, ?User $user, int $status): void {
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
        'version_id' => 1,
        'user_id' => $user?->id,
        'ip' => '127.0.0.1',
    ]);

    /** @var Package $package */
    $package = Package::query()->first();
    expect($package->downloads)->toBe(1);
})
    ->with(rootAndSubRepository(
        public: true
    ))
    ->with(guestAnd(Ability::REPOSITORY_READ));

it('downloads version from private repository', function (Repository $repository, ?User $user, int $status): void {
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
})
    ->with(rootAndSubRepository())
    ->with(guestAnd(Ability::REPOSITORY_READ, [401, 200]));
