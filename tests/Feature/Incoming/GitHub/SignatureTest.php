<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
use App\Models\Repository;
use App\Models\Source;

use function Pest\Laravel\postJson;

$event = ['ref' => 'refs/tags/0.1.3'];

it('requires valid signature', function (Repository $repository) use ($event): void {
    $source = Source::factory()
        ->provider(SourceProvider::GITHUB)
        ->create();

    /** @var string $content */
    $content = json_encode($event);

    postJson($repository->url("/incoming/github/$source->id"), $event)
        ->assertUnauthorized();

    postJson($repository->url("/incoming/github/$source->id"), $event, ['X-Hub-Signature-256' => 'incorrect'])
        ->assertUnauthorized();

    $signature = 'sha256='.hash_hmac('sha256', $content, (string) decrypt($source->secret));

    postJson($repository->url("/incoming/github/$source->id"), $event, ['X-Hub-Signature-256' => $signature])
        ->assertUnprocessable();
})->with(rootAndSubRepository());
