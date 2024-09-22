<?php

declare(strict_types=1);

use App\Enums\Ability;
use App\Models\Repository;

use function Pest\Laravel\getJson;

it('downloads a version', function (Repository $repository): void {
    getJson($repository->url('/test/test/1.0.0'))
        ->assertOk()
        ->assertContent((string) file_get_contents(__DIR__.'/../../Fixtures/project.zip'));
})->with(rootAndSubRepositoryFromZip(
    public: true
));

it('requires authentication', function (Repository $repository): void {
    getJson($repository->url('/test/test/1.0.0'))
        ->assertUnauthorized();
})->with(rootAndSubRepositoryFromZip());

it('requires ability', function (Repository $repository): void {
    user(Ability::REPOSITORY_READ);
    getJson($repository->url('/test/test/1.0.0'))
        ->assertOk();
})->with(rootAndSubRepositoryFromZip());
