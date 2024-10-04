<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\SourceProvider;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use App\Models\User;

use function Pest\Laravel\postJson;

it('stores', function (?User $user, int $status): void {
    $response = postJson('/sources', [
        'name' => $name = fake()->name,
        'provider' => $provider = fake()->randomElement(SourceProvider::cases()),
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
        ->url->toBe(\App\Normalizer::url($url))
        ->name->toBe($name)
        ->provider->toBe($provider)
        ->and(decrypt($source->token))->toBe($token);
})->with(guestAndUsers(Permission::SOURCE_CREATE, userWithPermission: 201));
