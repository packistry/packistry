<?php

declare(strict_types=1);

namespace App\Actions\Packages;

use App\Enums\MirrorStatus;
use App\Mirror\MetadataCache;
use App\Mirror\Resolver;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Upstream;
use Illuminate\Validation\ValidationException;

readonly class RefreshMirroredPackage
{
    public function __construct(
        private MetadataCache $metadata,
        private Resolver $resolver,
    ) {
        //
    }

    /**
     * Fetch the metadata of a mirrored package from its upstream now, and resolve the
     * package again when its upstream no longer has it.
     */
    public function handle(Package $package): Package
    {
        if (! $package->isMirrored()) {
            throw ValidationException::withMessages([
                'package' => 'Only mirrored packages can be refreshed from their upstream.',
            ]);
        }

        /** @var Upstream|null $upstream */
        $upstream = $package->upstream_id === null
            ? null
            : Upstream::query()->with('credentials')->find($package->upstream_id);

        if ($upstream !== null) {
            $result = $this->metadata->refresh($upstream, $package->name);
            $this->metadata->refresh($upstream, $package->name, dev: true);

            if ($result->isUnavailable() || $result->stale) {
                throw ValidationException::withMessages([
                    'package' => "$upstream->name is unavailable: ".($upstream->last_error ?? 'unknown error'),
                ]);
            }

            if ($result->isFound()) {
                $package->mirror_status = MirrorStatus::OK;
                $package->save();

                return $package->refresh();
            }
        }

        $package->mirror_status = MirrorStatus::GONE;
        $package->save();

        /** @var Repository $repository */
        $repository = Repository::query()->findOrFail($package->repository_id);

        return $this->resolver->resolve($repository, $package->name, existing: $package) ?? $package->refresh();
    }
}
