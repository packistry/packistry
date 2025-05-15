<?php

declare(strict_types=1);

use App\Normalizer;

it('normalizes url', function (string $url, string $expected): void {
    expect(Normalizer::url($url))->toBe($expected);
})
    ->with([
        'http with slash' => ['http://git.com/', 'http://git.com'],
        'no scheme with slash' => ['git.com/', 'https://git.com'],
        'no scheme without slash' => ['git.com', 'https://git.com'],
        'subdomain http with slash' => ['http://sub.git.com/', 'http://sub.git.com'],
        'subdomain no scheme with slash' => ['sub.git.com/', 'https://sub.git.com'],
        'subdomain no scheme without slash' => ['sub.git.com', 'https://sub.git.com'],

        'sub subdomain http with slash' => ['http://sub.sub.git.com/', 'http://sub.sub.git.com'],
        'sub subdomain no scheme with slash' => ['sub.sub.git.com/', 'https://sub.sub.git.com'],
        'sub subdomain no scheme without slash' => ['sub.sub.git.com', 'https://sub.sub.git.com'],
    ]);

it('normalizes version', function (string $url, string $expected): void {
    expect(Normalizer::version($url))->toBe($expected);
})
    ->with([
        'branch' => ['dev-feature', 'dev-feature'],
        'tag' => ['1.0.0', '1.0.0'],
        'tag with v prefix' => ['v1.0.0', '1.0.0'],
        'rc tag' => ['1.0-RC', '1.0-RC'],
        'rc tag with number' => ['1.0-RC1', '1.0-RC1'],
        'rc tag with v prefix' => ['v1.0-RC1', '1.0-RC1'],
        'non-semver segments' => ['1.2.3.4', '1.2.3.4'],
        'non-semver segments with v prefix' => ['v1.2.3.4', '1.2.3.4'],
    ]);
