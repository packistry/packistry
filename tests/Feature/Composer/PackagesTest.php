<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('provides urls', function (): void {
    getJson('/packages.json')
        ->assertOk()
        ->assertJsonContent([
            'search' => url('/search.json?q=%query%&type=%type%'),
            'metadata-url' => url('/p2/%package%.json'),
            'list' => url('/list.json'),
        ]);
});
