<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\DeployToken;
use App\Models\Package;
use App\Models\Version;
use Database\Factories\RepositoryFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotNull;

describe('Package-scoped access for /packages.json endpoint', function (): void {
    it('allows access to packages.json with package-scoped token in public repository', function (): void {
        $repository = rootRepository(public: true, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state(['name' => 'vendor/allowed']))
        );

        $package = $repository->packages->first();
        assertNotNull($package);

        deployTokenWithPackageAccess($package, TokenAbility::REPOSITORY_READ);

        getJson($repository->url('/packages.json'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'search',
                'metadata-url',
                'list',
            ]);
    });

    it('allows access to packages.json with package-scoped token in private repository', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state(['name' => 'vendor/allowed']))
        );

        $package = $repository->packages->first();
        assertNotNull($package);

        deployTokenWithPackageAccess($package, TokenAbility::REPOSITORY_READ);

        getJson($repository->url('/packages.json'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'search',
                'metadata-url',
                'list',
            ]);
    });

    it('denies access to packages.json for token without repository or package access', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state(['name' => 'vendor/package']))
        );

        // Token with no access
        deployToken(TokenAbility::REPOSITORY_READ);

        getJson($repository->url('/packages.json'))
            ->assertStatus(401);
    });
});

describe('Package-scoped access for /p2/{vendor}/{name}.json endpoint', function (): void {
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
});

describe('Package-scoped access for /list.json endpoint', function (): void {
    it('lists only authorized packages with package-scoped token', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state(['name' => 'vendor/allowed1']))
            ->has(Package::factory()->state(['name' => 'vendor/allowed2']))
            ->has(Package::factory()->state(['name' => 'vendor/denied']))
        );

        $allowed1 = $repository->packages->where('name', 'vendor/allowed1')->first();
        $allowed2 = $repository->packages->where('name', 'vendor/allowed2')->first();
        assertNotNull($allowed1);
        assertNotNull($allowed2);

        /** @var DeployToken $token */
        $token = DeployToken::factory()->create();
        actingAs($token, TokenAbility::REPOSITORY_READ);
        $token->packages()->sync([$allowed1->id, $allowed2->id]);

        getJson($repository->url('/list.json'))
            ->assertStatus(200)
            ->assertJsonPath('packageNames', [
                'vendor/allowed1',
                'vendor/allowed2',
            ]);
    });

    it('lists all packages with repository-level token', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state(['name' => 'vendor/package1']))
            ->has(Package::factory()->state(['name' => 'vendor/package2']))
            ->has(Package::factory()->state(['name' => 'vendor/package3']))
        );

        // Token with repository-level access
        deployToken(TokenAbility::REPOSITORY_READ, withAccess: true);

        getJson($repository->url('/list.json'))
            ->assertStatus(200)
            ->assertJsonPath('packageNames', [
                'vendor/package1',
                'vendor/package2',
                'vendor/package3',
            ]);
    });
});

describe('Package-scoped access for /search.json endpoint', function (): void {
    it('searches only authorized packages with package-scoped token', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state([
                'name' => 'vendor/allowed-foo',
                'description' => 'Allowed package with foo',
            ]))
            ->has(Package::factory()->state([
                'name' => 'vendor/denied-foo',
                'description' => 'Denied package with foo',
            ]))
        );

        $allowedPackage = $repository->packages->where('name', 'vendor/allowed-foo')->first();
        assertNotNull($allowedPackage);

        deployTokenWithPackageAccess($allowedPackage, TokenAbility::REPOSITORY_READ);

        getJson($repository->url('/search.json?q=vendor'))
            ->assertStatus(200)
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.name', 'vendor/allowed-foo');
    });

    it('searches all packages with repository-level token', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->state([
                'name' => 'vendor/package-foo',
                'description' => 'First package',
            ]))
            ->has(Package::factory()->state([
                'name' => 'vendor/another-foo',
                'description' => 'Second package',
            ]))
        );

        // Token with repository-level access
        deployToken(TokenAbility::REPOSITORY_READ, withAccess: true);

        getJson($repository->url('/search.json?q=vendor'))
            ->assertStatus(200)
            ->assertJsonCount(2, 'results');
    });
});

describe('Mixed repository and package access', function (): void {
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
});

describe('Edge cases', function (): void {
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

    it('allows access to public repository package without token', function (): void {
        $repository = rootRepository(public: true, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/public'])
            )
        );

        // No authentication
        getJson($repository->url('/p2/vendor/public.json'))
            ->assertStatus(200);
    });

    it('denies access to private repository package without token (returns 401 to prevent enumeration)', function (): void {
        $repository = rootRepository(public: false, closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()
                ->has(Version::factory()->count(1))
                ->state(['name' => 'vendor/private'])
            )
        );

        // No authentication
        getJson($repository->url('/p2/vendor/private.json'))
            ->assertStatus(401);
    });
});
