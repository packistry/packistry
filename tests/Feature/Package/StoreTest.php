<?php

declare(strict_types=1);

use App\Enums\PackageType;
use App\Enums\Permission;
use App\Enums\SourceProvider;
use App\Import;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Source;
use App\Models\User;
use App\Models\Version;
use App\Sources\Branch;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use Illuminate\Support\LazyCollection;

use function Pest\Laravel\postJson;

it('stores', function (?User $user, int $status, SourceProvider $provider): void {
    $repository = Repository::factory()->create();
    $source = Source::factory()
        ->provider($provider)
        ->create();

    /** @var string $content */
    $content = file_get_contents(__DIR__.'/../../Fixtures/project.zip');

    Http::fake([
        'https://fake.com/archive.zip' => Http::response($content, headers: ['content-type' => 'application/zip']),
    ]);

    $clientClassString = $provider->clientClassString();

    app()->singleton($clientClassString, function () use ($clientClassString) {
        /** @var Client $client */
        $client = new $clientClassString(app(Import::class));
        $client->withOptions(
            'token', 'https://url.url'
        );

        $mock = Mockery::mock($client)->shouldIgnoreMissing(false);

        $mock->shouldReceive('withOptions')->withAnyArgs()->andReturn($mock);

        $mock->shouldReceive('project')->withAnyArgs()->andReturn(new Project(
            id: 1,
            fullName: 'name/name',
            name: 'name',
            url: 'https://gitlab.com/name/name',
            webUrl: 'https://gitlab.com/name/name',
        ));

        $mock->shouldReceive('branches')->withAnyArgs()->andReturn(LazyCollection::wrap([
            new Branch(
                id: '1',
                name: 'feature',
                url: 'https://gitlab.com/name/name',
                zipUrl: 'https://fake.com/archive.zip',
            ),
        ]));

        $mock->shouldReceive('tags')->withAnyArgs()->andReturn(LazyCollection::wrap([
            new Tag(
                id: '1',
                name: '1.0.0',
                url: 'https://gitlab.com/name/name',
                zipUrl: 'https://fake.com/archive.zip',
            ),
        ]));

        $mock->shouldReceive('createWebhook')->withAnyArgs()->once();

        return $mock;
    });

    postJson('/packages', [
        'repository' => (string) $repository->id,
        'source' => (string) $source->id,
        'projects' => [
            '1',
        ],
        'webhook' => true,
    ])
        ->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    /** @var Package $package */
    $package = Package::query()->first();

    expect($package)
        ->repository_id->toBe(1)
        ->source_id->toBe(1)
        ->provider_id->toBe('1')
        ->name->toBe('test/test')
        ->latest_version->toBe('1.0.0')
        ->and($package)->type->toBe(PackageType::LIBRARY->value)
        ->description->toBe('description');

    /** @var Version $version */
    $version = Version::query()->find(1);

    expect($version)->not()->toBeNull()
        ->package_id->toBe(1)
        ->name->toBe('dev-feature')
        ->shasum->toBe('03d270009dd70e7a8a0c356b1a8ea6426bc464eb')
        ->metadata->toBe([
            'description' => 'description',
            'autoload' => [
                'psr-4' => [
                    'Test\Test\\' => 'src/',
                ],
            ],
            'authors' => [
                [
                    'name' => 'Test Test',
                    'email' => 'test@test.test',
                ],
            ],
            'require' => [],
        ]);

    /** @var Version $version */
    $version = Version::query()->find(2);

    expect($version)->not()->toBeNull()
        ->package_id->toBe(1)
        ->name->toBe('1.0.0')
        ->shasum->toBe('03d270009dd70e7a8a0c356b1a8ea6426bc464eb')
        ->metadata->toBe([
            'description' => 'description',
            'autoload' => [
                'psr-4' => [
                    'Test\Test\\' => 'src/',
                ],
            ],
            'authors' => [
                [
                    'name' => 'Test Test',
                    'email' => 'test@test.test',
                ],
            ],
            'require' => [],
        ]);
})
    ->with(guestAndUsers(Permission::PACKAGE_CREATE, userWithPermission: 201))
    ->with(SourceProvider::cases());
