<?php

declare(strict_types=1);

use App\Exceptions\InvalidTokenException;
use App\Models\Repository;
use App\Models\Source;
use App\Sources\GitLab\GitlabClient;
use App\Sources\Project;

beforeEach(function () {
    $this->gitLab = app(GitlabClient::class)->withOptions(
        token: $this->token = 'secret-token',
        url: 'https://gitlab.com',
    );

    $this->project = new Project(
        id: 278964,
        fullName: 'gitlab-org/gitlab',
        name: 'GitLab',
        url: 'https://gitlab.com/api/v4/projects/278964',
        webUrl: 'https://gitlab.com/gitlab-org/gitlab'
    );
});

it('has token on client', function () {
    expect($this->gitLab->http()->getOptions())
        ->toHaveKey('headers.Private-Token', $this->token);
});

it('accepts token with api scope', function () {
    Http::fake([
        'https://gitlab.com/api/v4/personal_access_tokens/self' => Http::response(File::get(__DIR__.'/../Fixtures/GitLab/token-self.json')),
    ]);

    $this->gitLab->validateToken();
})->throwsNoExceptions();

it('rejects token without api scope', function () {
    Http::fake([
        'https://gitlab.com/api/v4/personal_access_tokens/self' => Http::response(File::get(__DIR__.'/../Fixtures/GitLab/token-self-incorrect.json')),
    ]);

    $this->gitLab->validateToken();
})->throws(InvalidTokenException::class);

it('fetches projects', function () {
    Http::fake([
        'https://gitlab.com/api/v4/projects?*' => Http::response(File::get(__DIR__.'/../Fixtures/GitLab/projects.json'), headers: ['X-Total-Pages' => 1]),
    ]);

    $projects = $this->gitLab->projects('quality');

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
        'https://gitlab.com/api/v4/projects/278964' => Http::response(File::get(__DIR__.'/../Fixtures/GitLab/project.json')),
    ]);

    $project = $this->gitLab->project('278964');

    expect($project)
        ->id->toBe($this->project->id)
        ->fullName->toBe($this->project->fullName)
        ->name->toBe($this->project->name)
        ->url->toBe($this->project->url)
        ->webUrl->toBe($this->project->webUrl);
});

it('fetches project tags', function () {
    Http::fake([
        'https://gitlab.com/api/v4/projects/278964/repository/tags?per_page=100' => Http::response(File::get(__DIR__.'/../Fixtures/GitLab/tags.json')),
    ]);

    $tags = $this->gitLab->tags($this->project);

    expect($tags)
        ->toHaveCount(20)
        ->and($tags[0])
        ->id->toBe('278964')
        ->name->toBe('v17.6.5-ee')
        ->url->toBe('https://gitlab.com/api/v4/projects/278964')
        ->zipUrl->toBe('https://gitlab.com/api/v4/projects/278964/repository/archive.zip?sha=53bac28a6c50e5c1efa8b8d2520b24f32993682f');
});

it('fetches project branches', function () {
    Http::fake([
        'https://gitlab.com/api/v4/projects/278964/repository/branches?per_page=100' => Http::response(File::get(__DIR__.'/../Fixtures/GitLab/branches.json')),
    ]);

    $branches = $this->gitLab->branches($this->project);

    expect($branches)
        ->toHaveCount(15)
        ->and($branches[0])
        ->id->toBe('278964')
        ->name->toBe('00alkorba')
        ->url->toBe('https://gitlab.com/api/v4/projects/278964')
        ->zipUrl->toBe('https://gitlab.com/api/v4/projects/278964/repository/archive.zip?sha=95ffae76ca05765a4842f7af10b3977d04bc1221');
});

it('creates webhook', function () {
    Http::fake([
        'https://gitlab.com/api/v4/projects/278964/hooks' => Http::response(File::get(__DIR__.'/../Fixtures/GitLab/webhook.json')),
    ]);

    /** @var Repository $repository */
    $repository = Repository::factory()->make();
    $source = Source::factory()->make();

    $this->gitLab->createWebhook($repository, $this->project, $source);
})->throwsNoExceptions();
