<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\SourceProvider;
use App\Http\Resources\SourceResource;
use App\Import;
use App\Models\Source;
use App\Models\User;
use App\Normalizer;
use App\Sources\Client;

use function Pest\Laravel\postJson;

it('stores', function (?User $user, int $status, SourceProvider $provider): void {
    $clientClassString = $provider->clientClassString();
    app()->singleton($clientClassString, function () use ($clientClassString) {
        /** @var Client $client */
        $client = new $clientClassString(app(Import::class));

        $mock = Mockery::mock($client)->shouldIgnoreMissing(false);
        $mock->shouldReceive('withOptions')->withAnyArgs()->andReturn($mock);
        $mock->shouldReceive('validateToken')->withAnyArgs()->andReturn();

        return $mock;
    });

    $response = postJson('/sources', [
        'name' => $name = fake()->name,
        'provider' => $provider,
        'url' => $url = fake()->url,
        'token' => $token = Str::random(),
    ])->assertStatus($status);

    if ($status !== 201) {
        return;
    }

    $response->assertExactJson(
        resourceAsJson(
            new SourceResource(Source::query()->first()),
        )
    );

    /** @var Source $source */
    $source = Source::query()->first();

    expect($source)
        ->url->toBe(Normalizer::url($url))
        ->name->toBe($name)
        ->provider->toBe($provider)
        ->and(decrypt($source->token))->toBe($token);
})
    ->with(guestAndUsers(Permission::SOURCE_CREATE, userWithPermission: 201))
    ->with(SourceProvider::cases());
