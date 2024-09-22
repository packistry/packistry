<?php

declare(strict_types=1);

use App\Incoming\Gitea\Event\PushEvent;
use App\Incoming\Gitea\Repository as GiteaRepository;
use App\Models\Repository;
use App\Models\Version;

use function Pest\Laravel\postJson;

$event = new PushEvent(
    ref: 'refs/heads/feature-something',
    repository: new GiteaRepository(
        id: 1,
        name: 'test',
        fullName: 'group/test',
        htmlUrl: 'http://localhost:3000/group/test',
        url: 'http://localhost:3000/api/v1/repos/group/test',
    )
);

beforeEach(function () use ($event): void {
    /** @var string $content */
    $content = file_get_contents(__DIR__.'/../../../Fixtures/gitea-jamie-test.zip');

    Http::fake([
        $event->archiveUrl() => Http::response($content),
    ]);
});

it('creates dev version for new branch', function (Repository $repository) use ($event): void {
    $response = postJson($repository->url('/incoming/gitea'), $event->toArray(), eventHeaders($event))
        ->assertOk();

    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    $response->assertJsonContent([
        'package_id' => $version->package->id,
        'name' => $version->name,
        'shasum' => $version->shasum,
        'metadata' => $version->metadata,
        'updated_at' => $version->updated_at,
        'created_at' => $version->created_at,
        'id' => $version->id,
    ]);
})->with(rootAndSubRepository());

it('overwrites dev version for same branch', function (Repository $repository) use ($event): void {
    $response = postJson($repository->url('/incoming/gitea'), $event->toArray(), eventHeaders($event))
        ->assertOk();

    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    $response->assertJsonContent([
        'package_id' => $version->package->id,
        'name' => $version->name,
        'shasum' => $version->shasum,
        'metadata' => $version->metadata,
        'updated_at' => $version->updated_at,
        'created_at' => $version->created_at,
        'id' => $version->id,
    ]);
})->with(rootAndSubRepositoryFromZip(
    name: $event->repository->fullName,
    version: 'dev-'.$event->ref,
    zip: __DIR__.'/../../../Fixtures/gitea-jamie-test.zip',
    subDirectory: $event->repository->name.'/'
));
