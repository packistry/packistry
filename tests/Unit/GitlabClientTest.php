<?php

declare(strict_types=1);

use App\Exceptions\InvalidTokenException;
use App\Sources\GitLab\GitlabClient;

beforeEach(function () {
    $this->gitLab = app(GitlabClient::class)->withOptions(
        token: '',
        url: 'https://gitlab.com',
    );
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
