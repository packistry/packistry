<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Database\Factories\RepositoryFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotNull;

it('lists package versions', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    $package = $repository->packages->first();

    assertNotNull($package);

    getJson($repository->url('/p2/test/test.json'))
        ->assertStatus($status)
        ->assertExactJson([
            'minified' => 'composer/2.0',
            'packages' => [
                $package->name => $package->versions()
                    ->where('name', 'not like', 'dev-%')
                    ->where('name', 'not like', '%-dev')
                    ->get()
                    ->map(fn (Version $version) => [
                        ...$version->metadata,
                        'name' => $package->name,
                        'version' => $version->name,
                        'type' => $package->type,
                        'time' => $version->created_at,
                        'dist' => [
                            'type' => 'zip',
                            'url' => $package->repository->url("/$package->name/$version->name"),
                            'shasum' => $version->shasum,
                        ],
                    ])->toArray(),
            ],
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()
                    ->state(new Sequence(
                        fn (Sequence $sequence): array => ['name' => '0.1.'.$sequence->index],
                    ))
                    ->count(10)
                )
                ->devVersions(10)
                ->state([
                    'name' => 'test/test',
                ])
            )
    ))
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ));

it('requires ability', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    getJson($repository->url('/p2/test/test.json'))
        ->assertStatus($status);
})
    ->with(rootAndSubRepository(
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->state([
                    'name' => 'test/test',
                ])
            )
    ))
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_READ,
        guestStatus: 401,
        personalTokenWithoutAccessStatus: 401,
        deployTokenWithoutAccessStatus: 401,
    ));

describe('package-scoped access', function (): void {
    it('allows access to authorized package with package-scoped token', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()
                    ->state(new Sequence(
                        fn (Sequence $sequence): array => ['name' => '0.1.'.$sequence->index],
                    ))
                    ->count(3)
                )
                ->state(['name' => 'vendor/allowed'])
            )
        );

        $package = $repository->packages->first();
        assertNotNull($package);

        deployTokenWithPackageAccess($package, TokenAbility::REPOSITORY_READ);

        getJson($repository->url('/p2/vendor/allowed.json'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'minified',
                'packages' => [
                    'vendor/allowed',
                ],
            ]);
    });

    it('denies access to non-authorized package with package-scoped token', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/allowed'])
            )
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/denied'])
            )
        );

        $allowedPackage = $repository->packages->where('name', 'vendor/allowed')->first();
        assertNotNull($allowedPackage);

        deployTokenWithPackageAccess($allowedPackage, TokenAbility::REPOSITORY_READ);

        // Should be able to access allowed package
        getJson($repository->url('/p2/vendor/allowed.json'))
            ->assertStatus(200);

        // Should NOT be able to access denied package (404 to avoid leaking existence)
        getJson($repository->url('/p2/vendor/denied.json'))
            ->assertStatus(404);
    });

    it('allows access to all packages with repository-level token (backward compatibility)', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/package1'])
            )
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/package2'])
            )
        );

        // Token with repository-level access (no package selection)
        deployToken(TokenAbility::REPOSITORY_READ, withAccess: true);

        // Should be able to access both packages
        getJson($repository->url('/p2/vendor/package1.json'))
            ->assertStatus(200);

        getJson($repository->url('/p2/vendor/package2.json'))
            ->assertStatus(200);
    });

    it('allows access in public repository without authentication', function (): void {
        $repository = rootRepository(public: true, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/public'])
            )
        );

        // No authentication
        getJson($repository->url('/p2/vendor/public.json'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'minified',
                'packages' => [
                    'vendor/public',
                ],
            ]);
    });

    it('allows access to packages from repository-level AND package-level access', function (): void {
        // Create two repositories
        $repo1 = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/repo1-package1'])
            )
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/repo1-package2'])
            )
        );

        $repo2 = repository(path: 'other', public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/repo2-package'])
            )
        );

        // Get specific package from repo2
        $repo2Package = $repo2->packages->first();
        assertNotNull($repo2Package);

        // Token with access to ALL of repo1 + specific package from repo2
        deployTokenWithMixedAccess($repo1, $repo2Package, TokenAbility::REPOSITORY_READ);

        // Should access both packages from repo1
        getJson($repo1->url('/p2/vendor/repo1-package1.json'))
            ->assertStatus(200);

        getJson($repo1->url('/p2/vendor/repo1-package2.json'))
            ->assertStatus(200);

        // Should access specific package from repo2
        getJson($repo2->url('/p2/vendor/repo2-package.json'))
            ->assertStatus(200);
    });

    it('denies access for token with empty package list and no repository access', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/package'])
            )
        );

        // Token with no packages and no repositories
        deployToken(TokenAbility::REPOSITORY_READ);

        getJson($repository->url('/p2/vendor/package.json'))
            ->assertStatus(401);
    });

    it('returns 404 for non-existent package even with repository access', function (): void {
        $repository = rootRepository(public: false);

        deployToken(TokenAbility::REPOSITORY_READ, withAccess: true);

        getJson($repository->url('/p2/vendor/nonexistent.json'))
            ->assertStatus(404);
    });

    it('denies access to private repository package without token', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/private'])
            )
        );

        // No authentication - returns 401 to prevent enumeration
        getJson($repository->url('/p2/vendor/private.json'))
            ->assertStatus(401);
    });
});
