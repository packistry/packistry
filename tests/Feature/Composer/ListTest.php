<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Models\DeployToken;
use App\Models\Package;
use App\Models\Repository;
use Database\Factories\RepositoryFactory;
use Illuminate\Contracts\Auth\Authenticatable;

use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertNotNull;

it('lists packages', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    getJson($repository->url('/list.json'))
        ->assertStatus($status)
        ->assertExactJson([
            'packageNames' => $repository->packages->pluck('name'),
        ]);
})
    ->with(rootAndSubRepository(
        public: true,
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    ))
    ->with(guestAndTokens(TokenAbility::REPOSITORY_READ));

it('list packages from private repository', function (Repository $repository, ?Authenticatable $auth, int $status): void {
    getJson($repository->url('/list.json'))
        ->assertStatus($status);
})
    ->with(rootAndSubRepository(
        closure: fn (RepositoryFactory $factory) => $factory
            ->has(Package::factory()->count(10))
    ))
    ->with(guestAndTokens(
        abilities: TokenAbility::REPOSITORY_READ,
        guestStatus: 401,
        personalTokenWithoutAccessStatus: 401,
        deployTokenWithoutAccessStatus: 401,
    ));

describe('package-scoped access', function (): void {
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
