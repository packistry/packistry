<?php

declare(strict_types=1);

namespace App\Mirror;

use App\Models\Repository;

class DistUrlRewriter
{
    /**
     * Point every dist url to Packistry. Works on minified metadata as well: an entry only
     * carries a dist key when it differs from the previous entry, entries that inherit it
     * share the same reference and thus the same rewritten url.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function rewrite(array $data, Repository $repository): array
    {
        $packages = $data['packages'] ?? null;

        if (! is_array($packages)) {
            return $data;
        }

        foreach ($packages as $name => $versions) {
            if (! is_array($versions)) {
                continue;
            }

            foreach ($versions as $index => $version) {
                $dist = is_array($version) ? ($version['dist'] ?? null) : null;

                if (! is_array($dist) || ! is_string($dist['url'] ?? null)) {
                    continue;
                }

                $packages[$name][$index]['dist']['url'] = $repository->url("/_mirror/$name/".DistCache::key($dist));
            }
        }

        $data['packages'] = $packages;

        return $data;
    }
}
