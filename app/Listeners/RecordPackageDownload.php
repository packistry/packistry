<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PackageDownloadEvent;
use App\Models\Download;
use App\Models\Token;
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
        DB::transaction(function () use ($event): void {
            $version = $event->version;
            $download = new Download;
            $download->ip = $event->ip;

            if ($event->token instanceof Token) {
                $download->token_id = $event->token->id;
            }

            $download->package()->associate($event->package);
            $download->version_name = $version->name;

            $version
                ->downloads()
                ->save($download);

            $version->total_downloads += 1;
            $version->save();

            $event->package->total_downloads += 1;
            $event->package->save();
        });
    }
}
