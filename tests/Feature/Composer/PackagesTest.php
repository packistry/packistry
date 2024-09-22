<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Models\Repository;
use App\Models\User;

use function Pest\Laravel\getJson;

it('provides urls', function (Repository $repository, ?User $user, int $status): void {
    $prefix = is_null($repository->name) ? '' : $repository->name.'/';

    getJson($repository->url('/packages.json'))
        ->assertStatus($status)
        ->assertJsonContent([
            'search' => url($prefix.'search.json?q=%query%&type=%type%'),
            'metadata-url' => url($prefix.'p2/%package%.json'),
            'list' => url($prefix.'list.json'),
        ]);
})
    ->with(rootAndSubRepository(
        public: true
    ))
    ->with(guestAnd(Ability::REPOSITORY_READ));

it('provides urls from private repository', function (Repository $repository, ?User $user, int $status): void {
    getJson($repository->url('/packages.json'))
        ->assertStatus($status);
})
    ->with(rootAndSubRepository())
    ->with(guestAnd(Ability::REPOSITORY_READ, [401, 200]));
