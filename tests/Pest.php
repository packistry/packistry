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
use App\Incoming\Gitea\Event\DeleteEvent;
use App\Incoming\Gitea\Event\PushEvent;
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

function repository(string $name = 'sub', bool $public = false, ?Closure $closure = null): Repository
{
    return Repository::factory()
        ->state([
            'name' => $name,
        ])
        ->when($public, fn (RepositoryFactory $factory): RepositoryFactory => $factory->public())
        ->when(! is_null($closure), $closure)
        ->create();
}

function rootRepository(bool $public = false, ?Closure $closure = null): Repository
{
    return Repository::factory()
        ->when($public, fn (RepositoryFactory $factory): RepositoryFactory => $factory->public())
        ->when(! is_null($closure), $closure)
        ->root()
        ->create();
}

function rootWithPackageFromZip(bool $public = false, string $name = 'test/test', ?string $version = null, string $zip = __DIR__.'/Fixtures/project.zip', string $subDirectory = ''): Repository
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
                            ->fromZip($zip, $subDirectory, $version)
                    )
            )
    );
}

function repositoryWithPackageFromZip(bool $public = false, string $name = 'test/test', ?string $version = null, string $zip = __DIR__.'/Fixtures/project.zip', string $subDirectory = ''): Repository
{
    return repository(
        public: $public,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(
                Package::factory()
                    ->state([
                        'name' => $name,
                    ])
                    ->has(
                        Version::factory()
                            ->fromZip($zip, $subDirectory, $version)
                    )
            )
    );
}

/**
 * @return array<string, mixed>
 */
function rootAndSubRepository(bool $public = false, ?Closure $closure = null): array
{
    $prefix = $public ? 'public' : 'private';

    return [
        "$prefix repository (root)" => fn (): Repository => rootRepository(
            public: $public,
            closure: $closure
        ),
        "$prefix repository (sub)" => fn (): Repository => repository(
            public: $public,
            closure: $closure
        ),
    ];
}

/**
 * @return array<string, mixed>
 */
function rootAndSubRepositoryFromZip(bool $public = false, string $name = 'test/test', ?string $version = null, string $zip = __DIR__.'/Fixtures/project.zip', string $subDirectory = ''): array
{
    $prefix = $public ? 'public' : 'private';

    return [
        "$prefix repository (root)" => fn (): Repository => rootWithPackageFromZip(
            public: $public,
            name: $name,
            version: $version,
            zip: $zip,
            subDirectory: $subDirectory
        ),
        "$prefix repository (sub)" => fn (): Repository => repositoryWithPackageFromZip(
            public: $public,
            name: $name,
            version: $version,
            zip: $zip,
            subDirectory: $subDirectory
        ),
    ];
}

/**
 * @return array<string, mixed>
 */
function eventHeaders(PushEvent|DeleteEvent $event, string $secret = 'secret'): array
{
    $eventType = match ($event::class) {
        PushEvent::class => 'push',
        DeleteEvent::class => 'delete',
        default => throw new RuntimeException('unknown event')
    };

    return ['X-Hub-Signature-256' => eventSignature($event, $secret), 'X-Gitea-Event' => $eventType];
}

function eventSignature(mixed $event, string $secret): string
{
    $json = json_encode($event);

    if ($json === false) {
        throw new RuntimeException('failed to decode json');
    }

    return 'sha256='.hash_hmac('sha256', $json, $secret);
}

/**
 * @param  Ability|Ability[]  $abilities
 * @param  array{int, int}  $statuses
 * @return array<string, mixed>
 */
function guestAnd(Ability|array $abilities, array $statuses = [200, 200]): array
{
    $values = is_array($abilities)
        ? array_map(fn (Ability $ability) => $ability->value, $abilities)
        : [$abilities->value];

    $imploded = implode(',', $values);

    return [
        "$statuses[0] guest" => [
            fn (): null => null,
            $statuses[0],
        ],
        "$statuses[1] user ($imploded)" => [
            fn (): User => user($abilities),
            $statuses[1],
        ],
    ];
}
