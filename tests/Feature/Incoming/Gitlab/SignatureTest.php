<?php

declare(strict_types=1);

use App\Models\Repository;

use function Pest\Laravel\postJson;

$event = ['ref' => 'refs/tags/0.1.3'];

it('requires valid signature', function (Repository $repository) use ($event): void {
    postJson($repository->url('/incoming/gitlab'), $event)
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'secret missing',
        ]);

    $secret = 'secret';

    config()->set('services.gitlab.webhook.secret', $secret);

    postJson($repository->url('/incoming/gitlab'), $event, ['X-Gitlab-Token' => 'incorrect'])
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'invalid secret',
        ]);

    postJson($repository->url('/incoming/gitlab'), $event, ['X-Gitlab-Token' => $secret])
        ->assertOk()
        ->assertExactJson([
            'event' => ['unknown event type'],
        ]);
})->with(rootAndSubRepository());
