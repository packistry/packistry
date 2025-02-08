<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Resources\SourceResource;
use App\Import;
use App\Models\Source;
use App\Models\User;
use App\Normalizer;
use App\Sources\Client;
use function Pest\Laravel\patchJson;

it('updates', function (?User $user, int $status): void {
    $source = Source::factory()->create();
    $clientClassString = $source->provider->clientClassString();

    app()->singleton($source->provider->clientClassString(), function () use ($clientClassString) {
        /** @var Client $client */
        $client = new $clientClassString(app(Import::class));

        $mock = Mockery::mock($client)->shouldIgnoreMissing(false);
        $mock->shouldReceive('withOptions')->withAnyArgs()->andReturn($mock);
        $mock->shouldReceive('validateToken')->withAnyArgs()->andReturn();

        return $mock;
    });

    $response = patchJson("/sources/$source->id", [
        'name' => $name = fake()->name,
        'url' => $url = fake()->url,
        'token' => $token = Str::random(),
    ])
        ->assertStatus($status);

    if ($status !== 200) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(
            new SourceResource(Source::query()->first()),
        )
    );

    /** @var Source $source */
    $source = $source->fresh();

    expect($source)
        ->url->toBe(Normalizer::url($url))
        ->name->toBe($name)
        ->and(decrypt($source->token))->toBe($token);
})->with(guestAndUsers(Permission::SOURCE_UPDATE));

it('does not update token when not given', function (?User $user, int $status): void {
    $source = Source::factory()
        ->state([
            'token' => encrypt($token = Str::random()),
        ])
        ->create();

    patchJson("/sources/$source->id", [
        'name' => $name = fake()->name,
        'url' => $url = fake()->url,
    ])
        ->assertStatus($status);

    /** @var Source $source */
    $source = $source->fresh();

    expect($source)
        ->url->toBe(Normalizer::url($url))
        ->name->toBe($name)
        ->and(decrypt($source->token))->toBe($token);
})->with(unscopedUser(Permission::SOURCE_UPDATE));
