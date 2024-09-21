<?php

declare(strict_types=1);

use App\Enums\Ability;

use function Pest\Laravel\getJson;

it('provides urls', function (): void {
    rootRepository(public: true);

    getJson('/packages.json')
        ->assertOk()
        ->assertJsonContent([
            'search' => url('/search.json?q=%query%&type=%type%'),
            'metadata-url' => url('/p2/%package%.json'),
            'list' => url('/list.json'),
        ]);
});

it('requires authentication', function (): void {
    rootRepository();

    getJson('/packages.json')
        ->assertUnauthorized();
});

it('requires ability', function (): void {
    rootRepository();

    user(Ability::REPOSITORY_READ);
    getJson('/packages.json')
        ->assertOk();
});
