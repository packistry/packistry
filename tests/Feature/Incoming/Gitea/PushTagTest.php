<?php

declare(strict_types=1);

use App\Incoming\Gitea\Event\PushEvent;
use App\Incoming\Gitea\Repository as GiteaRepository;
use App\Models\Repository;
use App\Models\Version;

use function Pest\Laravel\postJson;

it('creates version for new tag', function (Repository $repository, string $tag, string $expectedVersion): void {
    $event = new PushEvent(
        ref: "refs/tags/$tag",
        repository: new GiteaRepository(
            id: 1,
            name: 'test',
            fullName: 'group/test',
            htmlUrl: 'http://localhost:3000/group/test',
            url: 'http://localhost:3000/api/v1/repos/group/test',
        )
    );

    /** @var string $content */
    $content = file_get_contents(__DIR__.'/../../../Fixtures/gitea-jamie-test.zip');

    Http::fake([
        $event->archiveUrl() => Http::response($content),
    ]);

    $response = postJson($repository->url('/incoming/gitea'), $event->toArray(), eventHeaders($event))
        ->assertOk();

    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    $response->assertJsonContent([
        'package_id' => $version->package->id,
        'name' => $expectedVersion,
        'shasum' => $version->shasum,
        'metadata' => $version->metadata,
        'updated_at' => $version->updated_at,
        'created_at' => $version->created_at,
        'id' => $version->id,
    ]);
})
    ->with(rootAndSubRepository())
    ->with([
        [
            'tag' => '1.0.0',
            'expectedVersion' => '1.0.0',
        ],
        [
            'tag' => 'v1.0.0',
            'expectedVersion' => '1.0.0',
        ],
    ]);

it('overwrites version for same tag', function (Repository $repository): void {
    $event = new PushEvent(
        ref: 'refs/tags/1.0.3',
        repository: new GiteaRepository(
            id: 1,
            name: 'test',
            fullName: 'group/test',
            htmlUrl: 'http://localhost:3000/group/test',
            url: 'http://localhost:3000/api/v1/repos/group/test',
        )
    );

    /** @var string $content */
    $content = file_get_contents(__DIR__.'/../../../Fixtures/gitea-jamie-test.zip');

    Http::fake([
        $event->archiveUrl() => Http::response($content),
    ]);

    $response = postJson($repository->url('/incoming/gitea'), $event->toArray(), eventHeaders($event))
        ->assertOk();

    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    $response->assertJsonContent([
        'id' => $version->id,
        'package_id' => $version->package->id,
        'name' => $version->name,
        'metadata' => $version->metadata,
        'shasum' => $version->shasum,
        'created_at' => $version->created_at,
        'updated_at' => $version->updated_at,
    ]);
})->with(rootAndSubRepositoryFromZip(
    name: 'group/test',
    version: '1.0.3',
    zip: __DIR__.'/../../../Fixtures/gitea-jamie-test.zip',
    subDirectory: 'test/'
));
