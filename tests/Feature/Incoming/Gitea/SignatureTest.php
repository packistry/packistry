<?php

declare(strict_types=1);

use App\Models\Repository;

use function Pest\Laravel\postJson;

$event = ['ref' => 'refs/tags/0.1.3'];

it('requires valid signature', function (Repository $repository) use ($event): void {
    /** @var string $content */
    $content = json_encode($event);

    postJson($repository->url('/incoming/gitea'), $event)
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'signature missing',
        ]);

    $secret = 'secret';

    config()->set('services.gitea.webhook.secret', $secret);

    postJson($repository->url('/incoming/gitea'), $event, ['X-Hub-Signature-256' => 'incorrect'])
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'signature validation failed',
        ]);

    $signature = 'sha256='.hash_hmac('sha256', $content, $secret);

    postJson($repository->url('/incoming/gitea'), $event, ['X-Hub-Signature-256' => $signature])
        ->assertOk()
        ->assertExactJson([
            'event' => ['unknown event type'],
        ]);
})->with(rootAndSubRepository());
