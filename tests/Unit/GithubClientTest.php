<?php

declare(strict_types=1);

use App\Exceptions\InvalidTokenException;
use App\Models\Repository;
use App\Models\Source;
use App\Normalizer;
use App\Sources\Branch;
use App\Sources\GitHub\GitHubClient;
use App\Sources\Project;
use App\Sources\Tag;
use Illuminate\Support\LazyCollection;

beforeEach(function () {
    $this->github = app(GitHubClient::class)->withOptions(
        token: $this->token = 'secret-token',
        url: 'https://api.github.com',
    );

    $this->project = new Project(
        id: 867865331,
        fullName: 'packistry/packistry',
        name: 'packistry',
        url: 'https://api.github.com/repos/packistry/packistry',
        webUrl: 'https://github.com/packistry/packistry'
    );
});

it('has token on client', function () {
    expect($this->github->http()->getOptions())
        ->toHaveKey('headers.Authorization', "Bearer {$this->token}");
});

it('accepts token with api scope', function () {
    Http::fake([
        'https://api.github.com/' => Http::response(
            headers: [
                'X-OAuth-Scopes' => 'foo,bar,repo',
            ],
        ),
    ]);

    $this->github->validateToken();
})->throwsNoExceptions();

it('rejects token without api scope', function () {
    Http::fake([
        'https://api.github.com/' => Http::response(
            headers: [
                'X-OAuth-Scopes' => 'foo,bar',
            ],
        ),
    ]);

    $this->github->validateToken();
})->throws(InvalidTokenException::class);

it('fetches projects', function () {
    Http::fake([
        'https://api.github.com/search/repositories?q=*' => Http::response(File::get(__DIR__.'/../Fixtures/Github/projects.json')),
    ]);

    $projects = $this->github->projects('packistry');

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
        "https://api.github.com/repositories/{$this->project->id}" => Http::response(File::get(__DIR__.'/../Fixtures/Github/project.json')),
    ]);

    $project = $this->github->project("{$this->project->id}");

    expect($project)
        ->id->toBe($this->project->id)
        ->fullName->toBe($this->project->fullName)
        ->name->toBe($this->project->name)
        ->url->toBe($this->project->url)
        ->webUrl->toBe($this->project->webUrl)
        ->readOnly->toBeFalse();
});

it('fetches project tags', function () {
    Http::fake([
        "{$this->project->url}/tags" => Http::response(
            File::get(__DIR__.'/../Fixtures/Github/tags-1.json'),
            headers: [
                'Link' => '<https://api.github.com/repositories/867865331/tags?page=2>; rel="next", <https://api.github.com/repositories/867865331/tags?page=2>; rel="last"',
            ],
        ),
        'https://api.github.com/repositories/867865331/tags?page=2' => Http::response(
            File::get(__DIR__.'/../Fixtures/Github/tags-2.json'),
            headers: [
                'Link' => '<https://api.github.com/repositories/867865331/tags?page=1>; rel="prev", <https://api.github.com/repositories/867865331/tags?page=1>; rel="first"',
            ],
        ),
    ]);

    $collection = $this->github->tags($this->project);

    expect($collection)
        ->toBeInstanceOf(LazyCollection::class);

    $tags = $collection->collect();

    expect($tags)
        ->toHaveCount(36)
        ->and($tags[0])
        ->toBeInstanceOf(Tag::class)
        ->id->toBe("{$this->project->id}")
        ->name->toBe('v0.11.0')
        ->url->toBe(Normalizer::url($this->project->webUrl))
        ->zipUrl->toBe('https://api.github.com/repos/packistry/packistry/zipball/refs/tags/v0.11.0');
});

it('fetches project branches', function () {
    Http::fake([
        "{$this->project->url}/branches" => Http::response(
            File::get(__DIR__.'/../Fixtures/Github/branches-1.json'),
            headers: [
                'Link' => '<https://api.github.com/repositories/867865331/branches?page=2>; rel="next", <https://api.github.com/repositories/867865331/branches?page=2>; rel="last"',
            ]
        ),
        'https://api.github.com/repositories/867865331/branches?page=2' => Http::response(File::get(__DIR__.'/../Fixtures/Github/branches-2.json')),
    ]);

    $collection = $this->github->branches($this->project);

    expect($collection)
        ->toBeInstanceOf(LazyCollection::class);

    $branches = $collection->collect();

    expect($branches)
        ->toHaveCount(5)
        ->and($branches[0])
        ->toBeInstanceOf(Branch::class)
        ->id->toBe("{$this->project->id}")
        ->name->toBe('favicon')
        ->url->toBe(Normalizer::url($this->project->webUrl))
        ->zipUrl->toBe("{$this->project->url}/zipball/refs/heads/favicon");
});

it('creates webhook', function () {
    Http::fake([
        "{$this->project->url}/hooks" => Http::response(),
    ]);

    /** @var Repository $repository */
    $repository = Repository::factory()->make();
    $source = Source::factory()->make();

    $this->github->createWebhook($repository, $this->project, $source);
})->throwsNoExceptions();
