<?php

declare(strict_types=1);

use App\Enums\SourceProvider;
use App\Models\Repository;
use App\Models\Source;

use function Pest\Laravel\postJson;

it('responds to ping event', function (Repository $repository): void {
    $source = Source::factory()
        ->provider(SourceProvider::GITHUB)
        ->create();

    postJson(
        uri: $repository->url("/incoming/github/$source->id"),
        data: [],
        headers: [
            'X-Hub-Signature-256' => eventSignature([], decrypt($source->secret)),
            'X-GitHub-Event' => 'ping',
        ])
        ->assertNoContent();
})->with(rootAndSubRepository());
