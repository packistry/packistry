<?php

declare(strict_types=1);

use App\Exceptions\InvalidTokenException;
use App\Sources\Gitea\GiteaClient;
use App\Sources\Project;

beforeEach(function () {
    $this->gitea = app(GiteaClient::class)->withOptions(
        token: '',
        url: 'https://gitea.com',
    );

    $this->project = new Project(
        id: 35024,
        fullName: 'gitea/act_runner',
        name: '-',
        url: 'https://gitea.com/api/v1/repos/gitea/act_runner',
        webUrl: 'https://gitea.com/gitea/act_runner'
    );

    Http::fake([
        'gitea.com/api/v1/repos/search*' => Http::response(File::get(__DIR__.'/../Fixtures/Gitea/repos-search.json')),
        'gitea.com/api/v1/repos/gitea/act_runner/branches' => Http::response(File::get(__DIR__.'/../Fixtures/Gitea/repos-branches.json')),
        'gitea.com/api/v1/repos/gitea/act_runner/tags' => Http::response(File::get(__DIR__.'/../Fixtures/Gitea/repos-tags.json')),
    ]);
});

it('accepts token with api scope', function () {
    Http::fake([
        'https://gitea.com/api/v1/repos/gitea/act_runner/hooks' => Http::response([], 422),
    ]);

    $this->gitea->validateToken();
})->throwsNoExceptions();

it('rejects token without api scope', function () {
    Http::fake([
        'https://gitea.com/api/v1/repos/gitea/act_runner/hooks' => Http::response([], 403),
    ]);

    $this->gitea->validateToken();
})->throws(InvalidTokenException::class);

it('can fetch projects', function () {
    $projects = $this->gitea->projects('gitea/act_runner');

    expect(count($projects))
        ->toBe(1)
        ->and($projects[0])
        ->id->toBe(35024)
        ->fullName->toBe('gitea/act_runner')
        ->name->toBe('act_runner')
        ->url->toBe('https://gitea.com/api/v1/repos/gitea/act_runner')
        ->webUrl->toBe('https://gitea.com/gitea/act_runner');
});

it('can fetch project branches', function () {
    $branches = $this->gitea->branches($this->project);

    expect(count($branches))
        ->toBe(2)
        ->and($branches[0])
        ->id()->toBe('35024')
        ->version()->toBe('dev-main')
        ->url()->toBe('https://gitea.com')
        ->zipUrl()->toBe('https://gitea.com/gitea/act_runner/archive/main.zip');
});

it('can fetch project tags', function () {
    $tags = $this->gitea->tags($this->project);

    expect(count($tags))
        ->toBe(22)
        ->and($tags[0])
        ->id()->toBe('35024')
        ->version()->toBe('v0.2.11')
        ->url()->toBe('https://gitea.com')
        ->zipUrl()->toBe('https://gitea.com/gitea/act_runner/archive/v0.2.11.zip');
});
