<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PackageDownloadEvent;
use App\Models\Download;
use App\Models\Token;
use App\Models\Version;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecordPackageDownload
{
    /**
     * @throws Throwable
     */
    public function handle(PackageDownloadEvent $event): void
    {
        /** @var Version $version */
        $version = $event->package
            ->versions()
            ->where('name', $event->version)
            ->firstOrFail();

        DB::transaction(function () use ($event, $version): void {
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

            $event->package->downloads += 1;
            $event->package->save();
        });
    }
}
