<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Version;
use App\Normalizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class NormalizeVersionOrder implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Version::query()
            ->chunk(100, function (Collection $versions) {
                dispatch(function () use ($versions) {
                    /** @var Version $version */
                    foreach ($versions as $version) {
                        $version->order = Normalizer::versionOrder($version->name);

                        $version->save();
                    }
                });
            });
    }
}
