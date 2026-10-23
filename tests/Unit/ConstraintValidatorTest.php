<?php

declare(strict_types=1);

use App\ConstraintValidator;

it('finds invalid constraints', function (array $metadata, array $expected): void {
    expect((new ConstraintValidator)->invalidConstraints($metadata))->toBe($expected);
})
    ->with([
        'accepts parsable constraints' => [
            ['require' => ['php' => '^8.4', 'laravel/framework' => '^12 || ^13']],
            [],
        ],
        'finds unparsable constraint' => [
            ['require' => ['php' => '^8.4', 'laravel/framework' => '^^12 || ^13']],
            ['require.laravel/framework'],
        ],
        'accepts empty link key' => [
            ['require' => []],
            [],
        ],
        'finds link key that is not an array' => [
            ['require' => 'laravel/framework', 'license' => 'MIT'],
            ['require'],
        ],
        'finds non string constraint' => [
            ['require' => ['php' => ['^8.4'], 'laravel/framework' => '^12']],
            ['require.php'],
        ],
        'accepts self.version' => [
            ['replace' => ['vendor/split-package' => 'self.version']],
            [],
        ],
        'checks all link keys' => [
            [
                'require' => ['php' => '^^8.4'],
                'require-dev' => ['pestphp/pest' => '^^4.0'],
                'conflict' => ['vendor/conflicting' => '^^1.0'],
                'provide' => ['psr/log-implementation' => '^^3.0'],
                'replace' => ['vendor/split-package' => '^^1.0'],
            ],
            [
                'require.php',
                'require-dev.pestphp/pest',
                'conflict.vendor/conflicting',
                'provide.psr/log-implementation',
                'replace.vendor/split-package',
            ],
        ],
        'ignores other keys' => [
            ['description' => 'A package.', 'suggest' => ['ext-foo' => '^^1.0']],
            [],
        ],
    ]);
