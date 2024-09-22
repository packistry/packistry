<?php

declare(strict_types=1);

use App\Incoming\Gitea\Event\DeleteEvent;
use App\Incoming\Gitea\Repository as GiteaRepository;
use App\Models\Repository;
use App\Models\Version;

use function Pest\Laravel\postJson;

$event = new DeleteEvent(
    ref: 'feature-something',
    refType: 'branch',
    pusherType: 'user',
    repository: new GiteaRepository(
        id: 1,
        name: 'test',
        fullName: 'vendor/test',
        htmlUrl: 'http://localhost:3000/jamie/test',
    )
);

it('deletes branch', function (Repository $repository) use ($event): void {
    /** @var Version $version */
    $version = Version::query()->latest('id')->first();

    postJson($repository->url('/incoming/gitea'), $event->toArray(), eventHeaders($event))
        ->assertOk()
        ->assertJsonContent([
            'id' => $version->id,
            'package_id' => $version->package->id,
            'name' => $version->name,
            'metadata' => $version->metadata,
            'shasum' => $version->shasum,
            'created_at' => $version->created_at,
            'updated_at' => $version->updated_at,
        ]);

    expect(Version::query()->count())->toBe(0);
})->with(rootAndSubRepositoryFromZip(
    name: $event->repository->fullName,
    version: 'dev-'.$event->ref,
    zip: __DIR__.'/../../../Fixtures/gitea-jamie-test.zip',
    subDirectory: $event->repository->name.'/'
));
