<?php

declare(strict_types=1);

namespace App\Mirror;

use App\Enums\MirrorStatus;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Upstream;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Decides which upstream owns a package name within a repository. The first upstream (in the
 * repository's priority order) that has the package wins, versions are never merged.
 */
readonly class Resolver
{
    /**
     * Composer's own package name rule. The name ends up in upstream urls and storage paths,
     * so anything else (dot segments, encoded query strings) is never asked from an upstream.
     */
    private const string PACKAGE_NAME = '{^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$}D';

    public function __construct(private MetadataCache $metadata)
    {
        //
    }

    public function resolve(Repository $repository, string $name, bool $dev = false, ?Package $existing = null): ?Package
    {
        if (preg_match(self::PACKAGE_NAME, $name) !== 1) {
            return null;
        }

        foreach ($repository->enabledUpstreams() as $upstream) {
            if (! $upstream->allows($name)) {
                continue;
            }

            $result = $this->metadata->get($upstream, $name);

            if ($result->isNotFound() && $dev) {
                $result = $this->metadata->get($upstream, $name, dev: true);
            }

            if ($result->isFound()) {
                return $this->claim($repository, $upstream, $name, $result, $existing);
            }

            // when a higher priority upstream cannot answer, a lower priority one must not claim
            // the name, otherwise a public package could shadow a private one during an outage
            if ($result->isUnavailable()) {
                return null;
            }
        }

        return null;
    }

    private function claim(
        Repository $repository,
        Upstream $upstream,
        string $name,
        MetadataResult $result,
        ?Package $existing,
    ): Package {
        $package = $existing ?? new Package;

        $package->repository_id = $repository->id;
        $package->name = $name;
        $package->upstream_id = $upstream->id;
        $package->mirror_status = MirrorStatus::OK;
        $package->forceFill(MetadataCache::details($result, $name));

        try {
            $package->save();
        } catch (UniqueConstraintViolationException) {
            // resolved concurrently by another request
            /** @var Package $package */
            $package = $repository->packages()->where('name', $name)->firstOrFail();
        }

        $package->setRelation('repository', $repository);
        $package->setRelation('upstream', $upstream);

        return $package;
    }
}
