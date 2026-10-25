<?php

declare(strict_types=1);

namespace App\Mirror;

use App\Models\Repository;

class DistUrlRewriter
{
    /**
     * Point every dist url of the package to Packistry. Works on minified metadata as well: an entry
     * only carries a dist key when it differs from the previous entry, entries that inherit it share
     * the same reference and thus the same rewritten url.
     *
     * Only the requested package is kept, anything else the upstream answers with is not served.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function rewrite(array $data, string $name, Repository $repository): array
    {
        $versions = $data['packages'][$name] ?? null;
        $versions = is_array($versions) ? array_values($versions) : [];

        foreach ($versions as $index => $version) {
            $dist = is_array($version) ? ($version['dist'] ?? null) : null;

            if (! is_array($dist) || ! is_string($dist['url'] ?? null)) {
                continue;
            }

            $versions[$index]['dist']['url'] = $repository->url("/_mirror/$name/".DistCache::key($dist));
        }

        $rewritten = [
            'packages' => [
                $name => $versions,
            ],
        ];

        if (isset($data['minified'])) {
            $rewritten['minified'] = $data['minified'];
        }

        return $rewritten;
    }
}
