<?php

declare(strict_types=1);

namespace App\Actions\Packages;

use App\Jobs\Batches\PackageImportBatch;
use App\Models\Package;
use RuntimeException;
use Throwable;

class RebuildPackage
{
    /**
     * @throws Throwable
     */
    public function handle(Package $package): void
    {
        $source = $package->source;

        if ($source === null || $package->provider_id === null) {
            throw new RuntimeException("Package $package->name [$package->id] has no source or provider id");
        }

        $project = $source->client()->project($package->provider_id);

        PackageImportBatch::make($source, $package, $project)
            ->dispatch();
    }
}
