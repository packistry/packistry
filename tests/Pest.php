<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Enums\Ability;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use App\Models\Version;
use Database\Factories\RepositoryFactory;
use Laravel\Sanctum\Sanctum;

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/**
 * @param  Ability|Ability[]  $abilities
 */
function user(Ability|array $abilities = []): User
{
    $user = User::factory()->create();

    actingAs($user, $abilities);

    return $user;
}

/**
 * @param  Ability|Ability[]  $abilities
 */
function actingAs(User $user, Ability|array $abilities = []): void
{
    $abilities = is_array($abilities)
        ? array_map(fn (Ability $ability) => $ability->value, $abilities)
        : [$abilities->value];

    Sanctum::actingAs($user, $abilities);
}

function rootRepository(bool $public = false, ?Closure $closure = null): Repository
{
    return Repository::factory()
        ->when($public, fn (RepositoryFactory $factory) => $factory->public())
        ->when(! is_null($closure), $closure)
        ->root()
        ->create();
}

function rootWithPackageFromZip(bool $public = false, string $name = 'test/test', string $zip = __DIR__.'/Fixtures/project.zip'): Repository
{
    return rootRepository(
        public: $public,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(
                Package::factory()
                    ->state([
                        'name' => $name,
                    ])
                    ->has(
                        Version::factory()
                            ->fromZip($zip)
                    )
            )
    );
}
