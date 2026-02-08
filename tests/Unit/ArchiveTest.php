<?php

declare(strict_types=1);

use App\Archive;
use App\Models\Package;
use App\Models\Repository;

it('replaces slashes in dev branch versions to prevent basename collision', function (?string $path, string $version, string $expected): void {
    $repository = Repository::factory()->make(['path' => $path]);

    $package = Package::factory()
        ->make(['name' => 'vendor/package']);

    $package->setRelation('repository', $repository);

    expect(Archive::name($package, $version))->toBe($expected);
})
    ->with([
        'root repo branch with slash' => [
            null,
            'dev-feature/statuses',
            'vendor-package-dev-feature~statuses.zip',
        ],
        'root repo branch with nested slashes' => [
            null,
            'dev-feature/scope/deep-branch',
            'vendor-package-dev-feature~scope~deep-branch.zip',
        ],
        'root repo simple dev branch without slash' => [
            null,
            'dev-main',
            'vendor-package-dev-main.zip',
        ],
        'root repo tagged version' => [
            null,
            '1.0.0',
            'vendor-package-1.0.0.zip',
        ],
        'branch with slash' => [
            'my-repo',
            'dev-feature/statuses',
            'my-repo/vendor-package-dev-feature~statuses.zip',
        ],
        'branch with nested slashes' => [
            'my-repo',
            'dev-feature/scope/deep-branch',
            'my-repo/vendor-package-dev-feature~scope~deep-branch.zip',
        ],
        'simple dev branch without slash' => [
            'my-repo',
            'dev-main',
            'my-repo/vendor-package-dev-main.zip',
        ],
        'tagged version' => [
            'my-repo',
            '1.0.0',
            'my-repo/vendor-package-1.0.0.zip',
        ],
    ]);
