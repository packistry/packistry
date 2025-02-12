<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
use App\Models\Repository;
use App\Models\Source;

use function Pest\Laravel\postJson;

it('requires valid signature', function (Repository $repository): void {
    $event = ['ref' => 'refs/tags/0.1.3'];

    $source = Source::factory()
        ->provider(SourceProvider::GITLAB)
        ->create();

    postJson($repository->url("/incoming/gitlab/$source->id"), $event)
        ->assertUnauthorized();

    postJson($repository->url("/incoming/gitlab/$source->id"), $event, ['X-Gitlab-Token' => 'incorrect'])
        ->assertUnauthorized();

    postJson($repository->url("/incoming/gitlab/$source->id"), $event, ['X-Gitlab-Token' => decrypt($source->secret)])
        ->assertUnprocessable();
})->with(rootAndSubRepository());
