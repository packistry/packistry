<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Models\Repository;

use function Pest\Laravel\getJson;

it('provides urls', function (Repository $repository): void {
    $prefix = is_null($repository->name) ? '' : $repository->name.'/';

    getJson($repository->url('/packages.json'))
        ->assertOk()
        ->assertJsonContent([
            'search' => url($prefix.'search.json?q=%query%&type=%type%'),
            'metadata-url' => url($prefix.'p2/%package%.json'),
            'list' => url($prefix.'list.json'),
        ]);
})->with(rootAndSubRepository(
    public: true
));

it('requires authentication', function (Repository $repository): void {
    getJson($repository->url('/packages.json'))
        ->assertUnauthorized();
})->with(rootAndSubRepository());

it('requires ability', function (Repository $repository): void {
    user(Ability::REPOSITORY_READ);
    getJson($repository->url('/packages.json'))
        ->assertOk();
})->with(rootAndSubRepository());
