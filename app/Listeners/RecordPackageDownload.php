<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PackageDownloadEvent;
use App\Models\Download;
use App\Models\Package;
use App\Models\Version;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecordPackageDownload implements ShouldQueue
{
    /**
     * @throws Throwable
     */
    public function handle(PackageDownloadEvent $event): void
    {
        /** @var Package $package */
        $package = $event
            ->repository
            ->packages()
            ->where('name', "$event->vendor/$event->name")
            ->firstOrFail();

        /** @var Version $version */
        $version = $package->versions()
            ->where('name', $event->version)
            ->firstOrFail();

        DB::transaction(function () use ($event, $package, $version): void {
            $download = new Download;
            $download->ip = $event->ip;
            $download->user_id = $event->user?->id;

            $version
                ->downloads()
                ->save($download);

            $package->downloads += 1;
            $package->save();
        });
    }
}
