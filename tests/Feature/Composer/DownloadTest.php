<?php

declare(strict_types=1);

use App\Enums\Ability;

use function Pest\Laravel\getJson;

it('downloads a version', function (): void {
    rootWithPackageFromZip(
        public: true,
    );

    getJson('/test/test/1.0.0')
        ->assertOk()
        ->assertContent((string) file_get_contents(__DIR__.'/../../Fixtures/project.zip'));
});

it('requires authentication', function (): void {
    rootWithPackageFromZip();

    getJson('/test/test/1.0.0')
        ->assertUnauthorized();
});

it('requires ability', function (): void {
    rootWithPackageFromZip(
        public: true,
    );

    user(Ability::REPOSITORY_READ);
    getJson('/test/test/1.0.0')
        ->assertOk();
});
