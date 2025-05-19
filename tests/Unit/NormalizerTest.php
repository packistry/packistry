<?php

declare(strict_types=1);

use App\Exceptions\VersionNotFoundException;
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
        'short rc tag with number' => ['1.0-RC1', '1.0-RC1'],
        'short rc tag with v prefix' => ['v1.0-RC1', '1.0-RC1'],
        'rc tag with number' => ['1.0.0-RC1', '1.0.0-RC1'],
        'rc tag with v prefix' => ['v1.0.0-RC1', '1.0.0-RC1'],
        '4 version segments' => ['1.2.3.4', '1.2.3.4'],
        '4 version segments with v prefix' => ['v1.2.3.4', '1.2.3.4'],
    ]);

it('fails to normalize unsupported versions', function (string $version): void {
    expect(fn (): string => Normalizer::version($version))
        ->toThrow(VersionNotFoundException::class);
})->with([
    '5-digit segments' => ['v1.0.0.0.0'],
]);

it('converts version to sort order', function (string $version, string $expected): void {
    expect(Normalizer::versionOrder($version))->toEqual($expected);
})->with([
    ['1.0.0', '01.0000000.0000000.0000000~'],
    ['1.2.3', '01.0000002.0000003.0000000~'],
    ['100.2.3', '100.0000002.0000003.0000000~'],
    ['2.100.2.3', '02.0000100.0000002.0000003~'],
    ['4.20.3', '04.0000020.0000003.0000000~'],
    ['3.0.0-RC1', '03.0000000.0000000.0000000-rc01'],
    ['3.0.0-RC10', '03.0000000.0000000.0000000-rc10'],
    ['3.0.0-RC', '03.0000000.0000000.0000000-rc00'],
    ['dev-foo', 'dev-foo'],
]);
