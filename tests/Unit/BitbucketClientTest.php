<?php

declare(strict_types=1);

use App\Models\Repository;
use App\Models\Source;
use App\Sources\Bitbucket\BitbucketClient;
use App\Sources\Branch;
use App\Sources\Project;
use App\Sources\Tag;
use Illuminate\Support\LazyCollection;

beforeEach(function () {
    $this->bitbucket = app(BitbucketClient::class)->withOptions(
        token: $this->token = 'secret-token',
        url: 'https://api.bitbucket.org',
        metadata: [
            'workspace' => 'packistry',
        ],
    );

    $this->project = new Project(
        id: '9f3c5c3b-9d20-4f2c-8f31-0f7ef9b1c4d0',
        fullName: 'packistry/packistry',
        name: 'packistry',
        url: 'https://api.bitbucket.org/2.0/repositories/packistry/packistry',
        webUrl: 'https://bitbucket.org/packistry/packistry'
    );
});

it('has token on client', function () {
    expect($this->bitbucket->http()->getOptions())
        ->toHaveKey('headers.Authorization', "Basic {$this->token}");
});

it('fetches projects', function () {
    Http::fake([
        'https://api.bitbucket.org/2.0/repositories/packistry*pagelen=1*' => Http::response(File::get(__DIR__.'/../Fixtures/Bitbucket/projects-initial.json')),
        'https://api.bitbucket.org/2.0/repositories/packistry*pagelen=100*' => Http::response(File::get(__DIR__.'/../Fixtures/Bitbucket/projects-page-1.json')),
    ]);

    $projects = $this->bitbucket->projects('packistry');

    expect($projects)
        ->toHaveCount(1)
        ->and($projects[0])
        ->id->toBe($this->project->id)
        ->fullName->toBe($this->project->fullName)
        ->name->toBe($this->project->name)
        ->url->toBe($this->project->url)
        ->webUrl->toBe($this->project->webUrl);
});

it('fetches project', function () {
    Http::fake([
        'https://api.bitbucket.org/2.0/repositories/packistry*uuid*' => Http::response(File::get(__DIR__.'/../Fixtures/Bitbucket/project.json')),
    ]);

    $project = $this->bitbucket->project($this->project->id);

    expect($project)
        ->id->toBe($this->project->id)
        ->fullName->toBe($this->project->fullName)
        ->name->toBe($this->project->name)
        ->url->toBe($this->project->url)
        ->webUrl->toBe($this->project->webUrl);
});

it('fetches project branches', function () {
    Http::fake([
        'https://api.bitbucket.org/2.0/repositories/packistry/packistry/refs/branches?pagelen=100&page=1' => Http::response(File::get(__DIR__.'/../Fixtures/Bitbucket/branches-page-1.json')),
        'https://api.bitbucket.org/2.0/repositories/packistry/packistry/refs/branches?pagelen=100&page=2' => Http::response(File::get(__DIR__.'/../Fixtures/Bitbucket/branches-page-2.json')),
    ]);

    $collection = $this->bitbucket->branches($this->project);

    expect($collection)
        ->toBeInstanceOf(LazyCollection::class);

    $branches = $collection->collect();

    expect($branches)
        ->toHaveCount(2)
        ->and($branches[0])
        ->toBeInstanceOf(Branch::class)
        ->id()->toBe($this->project->id)
        ->version()->toBe('dev-main')
        ->url()->toBe('https://bitbucket.org/packistry/packistry/branch/main')
        ->zipUrl()->toBe('https://bitbucket.org/packistry/packistry/get/main.zip');
});

it('fetches project tags', function () {
    Http::fake([
        'https://api.bitbucket.org/2.0/repositories/packistry/packistry/refs/tags?pagelen=100&page=1' => Http::response(File::get(__DIR__.'/../Fixtures/Bitbucket/tags-page-1.json')),
        'https://api.bitbucket.org/2.0/repositories/packistry/packistry/refs/tags?pagelen=100&page=2' => Http::response(File::get(__DIR__.'/../Fixtures/Bitbucket/tags-page-2.json')),
    ]);

    $collection = $this->bitbucket->tags($this->project);

    expect($collection)
        ->toBeInstanceOf(LazyCollection::class);

    $tags = $collection->collect();

    expect($tags)
        ->toHaveCount(2)
        ->and($tags[0])
        ->toBeInstanceOf(Tag::class)
        ->id()->toBe($this->project->id)
        ->version()->toBe('v1.0.0')
        ->url()->toBe('https://bitbucket.org/packistry/packistry/src/v1.0.0')
        ->zipUrl()->toBe('https://bitbucket.org/packistry/packistry/get/v1.0.0.zip');
});

it('creates webhook', function () {
    Http::fake([
        'https://api.bitbucket.org/2.0/repositories/packistry/packistry/hooks' => Http::response(),
    ]);

    /** @var Repository $repository */
    $repository = Repository::factory()->make();
    $source = Source::factory()->make();

    $this->bitbucket->createWebhook($repository, $this->project, $source);
})->throwsNoExceptions();
