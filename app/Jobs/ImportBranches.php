<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Package;
use App\Models\Source;
use App\Sources\Importable;
use App\Sources\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportBranches implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public function __construct(
        private readonly Source $source,
        private readonly Package $package,
        private readonly Project $project
    ) {
        //
    }

    public function handle(): void
    {
        $branches = $this->source->client()->branches($this->project);

        $jobs = array_map(fn (Importable $tag) => new ImportImportable(
            $this->source,
            $this->package,
            $tag,
        ), array_reverse($branches));

        $this->batch()?->add($jobs);
    }
}
