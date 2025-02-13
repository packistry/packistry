<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
use App\Models\Repository;
use App\Models\Source;

use function Pest\Laravel\postJson;

it('requires valid signature', function (Repository $repository): void {
    $event = ['ref' => 'refs/tags/0.1.3'];

    $source = Source::factory()
        ->provider(SourceProvider::BITBUCKET)
        ->create();

    /** @var string $content */
    $content = json_encode($event);

    postJson($repository->url("/incoming/bitbucket/$source->id"), $event)
        ->assertUnauthorized();

    postJson($repository->url("/incoming/bitbucket/$source->id"), $event, ['X-Hub-Signature-256' => 'incorrect'])
        ->assertUnauthorized();

    $signature = 'sha256='.hash_hmac('sha256', $content, (string) decrypt($source->secret));

    postJson($repository->url("/incoming/bitbucket/$source->id"), $event, ['X-Hub-Signature-256' => $signature])
        ->assertUnprocessable();
})->with(rootAndSubRepository());
