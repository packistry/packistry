<?php

declare(strict_types=1);

use App\MetadataSanitizer;

it('sanitizes metadata', function (array $metadata, array $expected): void {
    expect(MetadataSanitizer::sanitize($metadata))->toBe($expected);
})
    ->with([
        'keeps parsable constraints' => [
            ['require' => ['php' => '^8.4', 'laravel/framework' => '^12 || ^13']],
            ['require' => ['php' => '^8.4', 'laravel/framework' => '^12 || ^13']],
        ],
        'removes unparsable constraint, keeps parsable siblings' => [
            ['require' => ['php' => '^8.4', 'laravel/framework' => '^^12 || ^13']],
            ['require' => ['php' => '^8.4']],
        ],
        'keeps link key when no constraint remains' => [
            ['require' => ['laravel/framework' => '^^12'], 'license' => 'MIT'],
            ['require' => [], 'license' => 'MIT'],
        ],
        'keeps empty link key' => [
            ['require' => []],
            ['require' => []],
        ],
        'removes link key when not an array' => [
            ['require' => 'laravel/framework', 'license' => 'MIT'],
            ['license' => 'MIT'],
        ],
        'removes non string constraint' => [
            ['require' => ['php' => ['^8.4'], 'laravel/framework' => '^12']],
            ['require' => ['laravel/framework' => '^12']],
        ],
        'keeps self.version' => [
            ['replace' => ['vendor/split-package' => 'self.version']],
            ['replace' => ['vendor/split-package' => 'self.version']],
        ],
        'sanitizes all link keys' => [
            [
                'require' => ['php' => '^^8.4'],
                'require-dev' => ['pestphp/pest' => '^^4.0'],
                'conflict' => ['vendor/conflicting' => '^^1.0'],
                'provide' => ['psr/log-implementation' => '^^3.0'],
                'replace' => ['vendor/split-package' => '^^1.0'],
            ],
            [
                'require' => [],
                'require-dev' => [],
                'conflict' => [],
                'provide' => [],
                'replace' => [],
            ],
        ],
        'leaves other keys untouched' => [
            ['description' => 'A package.', 'autoload' => ['psr-4' => ['Vendor\\Package\\' => 'src']]],
            ['description' => 'A package.', 'autoload' => ['psr-4' => ['Vendor\\Package\\' => 'src']]],
        ],
    ]);
