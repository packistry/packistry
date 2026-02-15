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

use App\Enums\Permission;
use App\Enums\SourceProvider;
use App\Enums\TokenAbility;
use App\Models\DeployToken;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Source;
use App\Models\User;
use App\Sources\Bitbucket\Change;
use App\Sources\Bitbucket\Link;
use App\Sources\Bitbucket\Links;
use App\Sources\Bitbucket\Push;
use App\Sources\Bitbucket\Reference;
use App\Sources\Deletable;
use App\Sources\Gitea\Event\DeleteEvent;
use App\Sources\Gitea\Event\PushEvent;
use App\Sources\Gitea\Repository as GiteaRepository;
use App\Sources\Gitlab\Project;
use App\Sources\Importable;
use Database\Factories\RepositoryFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Testing\TestResponse;
use Spatie\LaravelData\Data;

use function Pest\Laravel\freezeSecond;
use function Pest\Laravel\postJson;
use function Pest\Laravel\travelBack;
use function PHPUnit\Framework\assertNotNull;

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->beforeEach(function () {
        freezeSecond();
    })
    ->afterEach(function () {
        travelBack();
    })
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit');

/**
 * @param  Permission|Permission[]  $permissions
 */
function user(Permission|array $permissions = []): User
{
    $permissions = is_array($permissions)
        ? $permissions
        : [$permissions];

    $user = User::factory()->create();

    config()->set("authorization.{$user->role->value}", $permissions);

    app('auth')->guard('sanctum')->setUser($user);
    app('auth')->shouldUse('sanctum');

    return $user;
}

/**
 * @param  TokenAbility|TokenAbility[]  $abilities
 * @param  Permission|Permission[]  $permissions
 */
function personalToken(TokenAbility|array $abilities = [], bool $withAccess = false, Permission|array $permissions = []): User
{
    $permissions = is_array($permissions)
        ? $permissions
        : [$permissions];

    $user = User::factory()->create();
    config()->set("authorization.{$user->role->value}", $permissions);
    actingAs($user, $abilities);

    if ($withAccess) {
        $user->repositories()->sync([1]);
    }

    return $user;
}

/**
 * @param  TokenAbility|TokenAbility[]  $abilities
 */
function deployToken(TokenAbility|array $abilities = [], bool $withAccess = false, bool $expired = false, ?array $withPackages = null): DeployToken
{
    /** @var DeployToken $token */
    $token = Deploytoken::factory()
        ->create();

    actingAs($token, $abilities, expiresAt: $expired ? now()->subSecond() : null);

    if ($withAccess) {
        $token->repositories()->sync([1]);
    }

    if ($withPackages) {
        $token->packages()->sync($withPackages);
    }

    return $token;
}

/**
 * Create a deploy token with package-level access.
 *
 * @param  Package|array<Package>  $packages  Package(s) to grant access to
 * @param  TokenAbility|TokenAbility[]  $abilities
 */
function deployTokenWithPackageAccess(Package|array $packages, TokenAbility|array $abilities = []): DeployToken
{
    /** @var DeployToken $token */
    $token = DeployToken::factory()->create();

    actingAs($token, $abilities);

    // Grant access to specific package(s)
    $packageIds = is_array($packages)
        ? array_map(fn (Package $p) => $p->id, $packages)
        : [$packages->id];

    $token->packages()->sync($packageIds);

    return $token;
}

/**
 * Create a deploy token with mixed repository and package access.
 *
 * @param  Repository|array<Repository>  $repositories  Repository(ies) to grant access to
 * @param  Package|array<Package>  $packages  Package(s) to grant access to
 * @param  TokenAbility|TokenAbility[]  $abilities
 */
function deployTokenWithMixedAccess(Repository|array $repositories, Package|array $packages, TokenAbility|array $abilities = []): DeployToken
{
    /** @var DeployToken $token */
    $token = DeployToken::factory()->create();

    actingAs($token, $abilities);

    // Grant access to repository(ies)
    $repositoryIds = is_array($repositories)
        ? array_map(fn (Repository $r) => $r->id, $repositories)
        : [$repositories->id];

    $token->repositories()->sync($repositoryIds);

    // Grant access to specific package(s)
    $packageIds = is_array($packages)
        ? array_map(fn (Package $p) => $p->id, $packages)
        : [$packages->id];

    $token->packages()->sync($packageIds);

    return $token;
}

/**
 * @param  TokenAbility|TokenAbility[]  $abilities
 */
function actingAs(User|DeployToken $subject, TokenAbility|array $abilities = [], ?DateTimeInterface $expiresAt = null): void
{
    $abilities = is_array($abilities)
        ? array_map(fn (TokenAbility $ability) => $ability->value, $abilities)
        : [$abilities->value];

    $new = $subject->createToken('token', $abilities, $expiresAt);

    $subject->withAccessToken($new->accessToken);

    if (isset($subject->wasRecentlyCreated) && $subject->wasRecentlyCreated) {
        $subject->wasRecentlyCreated = false;
    }

    app('auth')->guard('sanctum')->setUser($subject);
    app('auth')->shouldUse('sanctum');
}

function repository(string $path = 'sub', bool $public = false, ?Closure $closure = null): Repository
{
    return Repository::factory()
        ->state([
            'path' => $path,
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
function unscopedUser(Permission $permission, int $expectedStatus = 200): array
{
    return [
        "$expectedStatus user (unscoped, $permission->value)" => [
            fn (): User => user([Permission::UNSCOPED, $permission]),
            $expectedStatus,
        ],
    ];
}

/**
 * @param  Permission|Permission[]  $permissions
 * @return array<string, mixed>
 */
function guestAndUsers(
    Permission|array $permissions,
    int $guestStatus = 401,
    int $userWithoutPermission = 403,
    int $userWithPermission = 200,
): array {
    $values = is_array($permissions)
        ? array_map(fn (Permission $ability) => $ability->value, $permissions)
        : [$permissions->value];

    $imploded = implode(',', $values);

    return [
        "$guestStatus guest" => [
            fn (): null => null,
            $guestStatus,
        ],
        "$userWithoutPermission user" => [
            fn (): User => user(),
            $userWithoutPermission,
        ],
        "$userWithPermission user ($imploded)" => [
            fn (): User => user($permissions),
            $userWithPermission,
        ],
    ];
}

/**
 * @param  TokenAbility|TokenAbility[]  $abilities
 * @return array<string, mixed>
 */
function guestAndTokens(
    TokenAbility|array $abilities,
    int $guestStatus = 200,
    int $personalTokenWithoutAccessStatus = 200,
    int $personalTokenWithAccessStatus = 200,
    int $unscopedPersonalTokenWithoutAccessStatus = 200,
    int $deployTokenWithoutAccessStatus = 200,
    int $deployTokenWithAccessStatus = 200,
    int $deployTokenWithoutPackagesStatus = 200,
    int $deployTokenWithPackagesStatus = 200,
    ?array $deployTokenPackages = null,
    int $expiredDeployTokenWithAccessStatus = 401,
): array {
    $values = is_array($abilities)
        ? array_map(fn (TokenAbility $ability) => $ability->value, $abilities)
        : [$abilities->value];

    $imploded = implode(',', $values);

    $dataset = [
        "$guestStatus guest" => [
            fn (): null => null,
            $guestStatus,
            null,
        ],
        "$personalTokenWithoutAccessStatus user without access ($imploded)" => [
            fn (): User => personalToken($abilities),
            $personalTokenWithoutAccessStatus,
            null,
        ],
        "$personalTokenWithAccessStatus user with access ($imploded)" => [
            fn (): User => personalToken($abilities, withAccess: true),
            $personalTokenWithAccessStatus,
            null,
        ],
        "$unscopedPersonalTokenWithoutAccessStatus unscoped user without access ($imploded)" => [
            fn (): User => personalToken($abilities, permissions: Permission::UNSCOPED),
            $unscopedPersonalTokenWithoutAccessStatus,
            null,
        ],
        "$deployTokenWithoutAccessStatus deploy token without access ($imploded)" => [
            fn (): DeployToken => deployToken($abilities),
            $deployTokenWithoutAccessStatus,
            null,
        ],
        "$deployTokenWithAccessStatus deploy token with access ($imploded)" => [
            fn (): DeployToken => deployToken($abilities, withAccess: true),
            $deployTokenWithAccessStatus,
            null,
        ],
        "$deployTokenWithoutPackagesStatus deploy token without packages ($imploded)" => [
            fn (): DeployToken => deployToken($abilities, withPackages: []),
            $deployTokenWithoutPackagesStatus,
            [],
        ],
        "$expiredDeployTokenWithAccessStatus expired deploy token with access ($imploded)" => [
            fn (): DeployToken => deployToken($abilities, withAccess: true, expired: true),
            $expiredDeployTokenWithAccessStatus,
            null,
        ],
    ];

    if (! is_null($deployTokenPackages)) {
        $dataset["$deployTokenWithPackagesStatus deploy token with access to packages ($imploded)"] = [
            fn (): DeployToken => deployToken($abilities, withPackages: $deployTokenPackages),
            $deployTokenWithPackagesStatus,
            $deployTokenPackages,
        ];
    }

    return $dataset;
}

/**
 * @return array<string, mixed>
 */
function giteaEventHeaders(Importable|Deletable $event, string $secret = 'secret'): array
{
    $eventType = match ($event::class) {
        PushEvent::class => 'push',
        DeleteEvent::class => 'delete',
        default => throw new RuntimeException('unknown event')
    };

    return ['X-Hub-Signature-256' => eventSignature($event, $secret), 'X-Gitea-Event' => $eventType];
}

/**
 * @return array<string, mixed>
 */
function githubEventHeaders(Importable|Deletable $event, string $secret = 'secret'): array
{
    $eventType = match ($event::class) {
        \App\Sources\GitHub\Event\PushEvent::class => 'push',
        \App\Sources\GitHub\Event\DeleteEvent::class => 'delete',
        default => throw new RuntimeException('unknown event')
    };

    return ['X-Hub-Signature-256' => eventSignature($event, $secret), 'X-GitHub-Event' => $eventType];
}

/**
 * @return array<string, mixed>
 */
function bitbucketEventHeaders(Importable|Deletable $event, string $secret = 'secret'): array
{
    $eventType = match ($event::class) {
        \App\Sources\Bitbucket\Event\PushEvent::class => 'repo:push',
        default => throw new RuntimeException('unknown event')
    };

    return ['X-Hub-Signature-256' => eventSignature($event, $secret), 'X-Event-Key' => $eventType];
}

/**
 * @return array<string, mixed>
 */
function eventHeaders(Importable|Deletable $event, string $secret = 'secret'): array
{
    return match ($event::class) {
        PushEvent::class, DeleteEvent::class => giteaEventHeaders($event, $secret),
        \App\Sources\GitHub\Event\DeleteEvent::class, \App\Sources\GitHub\Event\PushEvent::class => githubEventHeaders($event, $secret),
        \App\Sources\Gitlab\Event\PushEvent::class => gitlabEventHeader($secret),
        \App\Sources\Bitbucket\Event\PushEvent::class => bitbucketEventHeaders($event, $secret),
    };
}

/**
 * @return array<string, mixed>
 */
function gitlabEventHeader(string $secret = 'secret'): array
{
    return ['X-Gitlab-Token' => $secret, 'X-Gitlab-Event' => 'Push Hook'];
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
 * @return array<string, mixed>
 */
function providerPushEvents(string $refType = 'tags', string $ref = '1.0.0'): array
{
    return [
        'gitea' => [
            'provider' => SourceProvider::GITEA,
            'event' => new PushEvent(
                ref: "refs/$refType/$ref",
                repository: new GiteaRepository(
                    id: 1,
                    name: 'test',
                    fullName: 'vendor/test',
                    htmlUrl: 'https://gitea.com/vendor/test',
                    url: 'https://gitea.com/api/v1/repos/vendor/test',
                )
            ),
            'archivePath' => __DIR__.'/Fixtures/gitea-jamie-test.zip',
        ],
        'github' => [
            'provider' => SourceProvider::GITHUB,
            'event' => new \App\Sources\GitHub\Event\PushEvent(
                ref: "refs/$refType/$ref",
                repository: new \App\Sources\GitHub\Repository(
                    id: 1,
                    name: 'test',
                    fullName: 'vendor/test',
                    htmlUrl: 'https://github.com/vendor/test',
                    url: 'https://api.github.com/repos/vendor/test',
                )
            ),
            'archivePath' => __DIR__.'/Fixtures/gitea-jamie-test.zip',
        ],
        'gitlab' => [
            'provider' => SourceProvider::GITLAB,
            'event' => new \App\Sources\Gitlab\Event\PushEvent(
                ref: "refs/$refType/$ref",
                after: 'after',
                before: 'before',
                checkoutSha: 'checkout-sha',
                project: new Project(
                    id: 1,
                    name: 'test',
                    pathWithNamespace: 'vendor/test',
                    webUrl: 'https://gitlab.com/group/test',
                )
            ),
            'archivePath' => __DIR__.'/Fixtures/gitlab-jamie-test.zip',
        ],
        'bitbucket' => [
            'provider' => SourceProvider::BITBUCKET,
            'event' => new \App\Sources\Bitbucket\Event\PushEvent(
                push: new Push(
                    changes: [
                        new Change(
                            old: null,
                            new: new Reference(
                                name: $ref,
                                type: $refType === 'heads' ? 'commit' : 'tag',
                            ),
                        ),
                    ]
                ),
                repository: new \App\Sources\Bitbucket\Repository(
                    name: 'test',
                    fullName: 'vendor/test',
                    uuid: '{1}',
                    links: new Links(
                        html: new Link(
                            href: 'https://bitbucket.org/vendor/test'
                        ),
                        self: new Link(
                            href: 'https://api.bitbucket.org/2.0/repositories/vendor/test'
                        ),
                    )
                )
            ),
            'archivePath' => __DIR__.'/Fixtures/gitlab-jamie-test.zip',
        ],
    ];
}

function fakeZipArchiveDownload(Importable $event, string $archivePath): void
{
    /** @var string $content */
    $content = file_get_contents($archivePath);

    Http::fake([
        $event->zipUrl() => Http::response($content, headers: ['content-type' => 'application/zip']),
    ]);
}

/**
 * @return TestResponse<JsonResponse>
 */
function webhook(Repository $repository, ?Source $source, (Importable&Data)|(Deletable&data) $event, ?string $archivePath = null): TestResponse
{
    assertNotNull($source);

    if (! is_null($archivePath) && $event instanceof Importable) {
        fakeZipArchiveDownload($event, $archivePath);
    }

    return postJson($repository->url("/incoming/{$source->provider->value}/$source->id"), $event->toArray(), eventHeaders($event));
}

/**
 * @return array<string, mixed>
 */
function providerDeleteEvents(string $refType = 'tags', string $ref = '1.0.0'): array
{
    return [
        'gitea' => [
            'provider' => SourceProvider::GITEA,
            'event' => new DeleteEvent(
                ref: $ref,
                refType: $refType === 'heads' ? 'branch' : 'tag',
                pusherType: 'user',
                repository: new GiteaRepository(
                    id: 1,
                    name: 'test',
                    fullName: 'vendor/test',
                    htmlUrl: 'https://gitea.com/vendor/test',
                    url: 'https://gitea.com/api/v1/repos/vendor/test',
                )
            ),
        ],
        'github' => [
            'provider' => SourceProvider::GITHUB,
            'event' => new \App\Sources\GitHub\Event\DeleteEvent(
                ref: $ref,
                refType: $refType === 'heads' ? 'branch' : 'tag',
                pusherType: 'user',
                repository: new \App\Sources\GitHub\Repository(
                    id: 1,
                    name: 'test',
                    fullName: 'vendor/test',
                    htmlUrl: 'https://github.com/vendor/test',
                    url: 'https://github.com/vendor/test',
                )
            ),
        ],
        'gitlab' => [
            'provider' => SourceProvider::GITLAB,
            'event' => new \App\Sources\Gitlab\Event\PushEvent(
                ref: "refs/$refType/$ref",
                after: '0000000000000000000000000000000000000000',
                before: 'before',
                checkoutSha: null,
                project: new Project(
                    id: 1,
                    name: 'test',
                    pathWithNamespace: 'vendor/test',
                    webUrl: 'https://gitlab.com',
                )
            ),
        ],
        'bitbucket' => [
            'provider' => SourceProvider::BITBUCKET,
            'event' => new \App\Sources\Bitbucket\Event\PushEvent(
                push: new Push(
                    changes: [
                        new Change(
                            old: new Reference(
                                name: $ref,
                                type: $refType === 'heads' ? 'commit' : 'tag',
                            ),
                            new: null,
                        ),
                    ]
                ),
                repository: new \App\Sources\Bitbucket\Repository(
                    name: 'test',
                    fullName: 'vendor/test',
                    uuid: '{1}',
                    links: new Links(
                        html: new Link(
                            href: 'https://bitbucket.org/vendor/test'
                        ),
                        self: new Link(
                            href: 'https://api.bitbucket.org/2.0/repositories/vendor/test'
                        ),
                    )
                )
            ),
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function resourceAsJson(JsonResource $resource): array
{
    return json_decode($resource->toJson(), true);
}

/**
 * @param  array<string, string[]>  $errors
 * @return array{message: string, errors: array<string, string[]>}
 */
function validation(array $errors): array
{
    $size = count($errors);

    if ($size === 1) {
        return [
            'message' => $errors[array_key_first($errors)][0],
            'errors' => $errors,
        ];
    }

    $count = $size - 1;

    return [
        'message' => "{$errors[array_key_first($errors)][0]} ".trans_choice('(and :count more errors)', $count),
        'errors' => $errors,
    ];
}
