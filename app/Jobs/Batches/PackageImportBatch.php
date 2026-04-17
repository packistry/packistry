<?php

declare(strict_types=1);

namespace App\Jobs\Batches;

use App\Jobs\ImportBranches;
use App\Jobs\ImportTags;
use App\Models\Package;
use App\Models\Source;
use App\Sources\Project;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;

class PackageImportBatch
{
    public static function make(
        Source $source,
        Package $package,
        Project $project,
        bool $skipExisting = false
    ): PendingBatch {
        return Bus::batch([
            new ImportBranches($source, $package, $project, $skipExisting),
            new ImportTags($source, $package, $project, $skipExisting),
        ])
            ->name(self::class)
            ->withOption('package', $package)
            ->allowFailures();
    }
}
