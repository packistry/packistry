<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\SourceProvider;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use App\Models\User;

use function Pest\Laravel\patchJson;

it('updates', function (?User $user, int $status): void {
    $source = Source::factory()->create();

    $response = patchJson("/sources/$source->id", [
        'name' => $name = fake()->name,
        'provider' => $provider = fake()->randomElement(SourceProvider::cases()),
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
        ->url->toBe(\App\Normalizer::url($url))
        ->name->toBe($name)
        ->provider->toBe($provider)
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
        'provider' => $provider = fake()->randomElement(SourceProvider::cases()),
        'url' => $url = fake()->url,
    ])
        ->assertStatus($status);

    /** @var Source $source */
    $source = $source->fresh();

    expect($source)
        ->url->toBe(\App\Normalizer::url($url))
        ->name->toBe($name)
        ->provider->toBe($provider)
        ->and(decrypt($source->token))->toBe($token);
})->with(unscopedUser(Permission::SOURCE_UPDATE));
