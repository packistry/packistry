<?php

declare(strict_types=1);

namespace App\Actions\Upstreams;

use App\Enums\MirrorStatus;
use App\Models\Package;
use App\Models\Upstream;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DestroyUpstream
{
    /**
     * @throws Throwable
     */
    public function handle(Upstream $upstream): Upstream
    {
        $directory = $upstream->storagePath();

        DB::transaction(function () use ($upstream): void {
            // keep mirrored packages (and their download history), they resolve again on the next request
            Package::query()
                ->where('upstream_id', $upstream->id)
                ->update(['mirror_status' => MirrorStatus::GONE->value]);

            $upstream->delete();
        });

        dispatch(function () use ($directory): void {
            Storage::deleteDirectory($directory);
        });

        return $upstream;
    }
}
