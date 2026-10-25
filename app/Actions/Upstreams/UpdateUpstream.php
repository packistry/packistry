<?php

declare(strict_types=1);

namespace App\Actions\Upstreams;

use App\Actions\Upstreams\Inputs\UpdateUpstreamInput;
use App\Models\MirrorDist;
use App\Models\MirrorMetadata;
use App\Models\Upstream;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

readonly class UpdateUpstream
{
    public function __construct(private SyncUpstreamCredentials $syncCredentials)
    {
        //
    }

    /**
     * @throws Throwable
     */
    public function handle(Upstream $upstream, UpdateUpstreamInput $input): Upstream
    {
        return DB::transaction(function () use ($upstream, $input): Upstream {
            $upstream->name = $input->name;
            $upstream->url = rtrim($input->url, '/');
            $upstream->only = StoreUpstream::patterns($input->only);
            $upstream->exclude = StoreUpstream::patterns($input->exclude);

            if ($upstream->isDirty('url')) {
                $this->forgetPreviousUrl($upstream);
            }

            $upstream->save();

            $this->syncCredentials->handle($upstream, $input->credentials);

            if ($upstream->isPrivate() && $upstream->repositories()->where('public', true)->exists()) {
                throw ValidationException::withMessages([
                    'credentials' => 'This upstream is attached to a public repository, '
                        .'it cannot have credentials.',
                ]);
            }

            return $upstream;
        });
    }

    /**
     * Metadata and dists cached from the previous url must not be served as if they came from the new one.
     * Mirrored packages stay, the next request fetches their metadata from the new url.
     */
    private function forgetPreviousUrl(Upstream $upstream): void
    {
        $upstream->metadata_url = null;
        $upstream->root_checked_at = null;

        MirrorMetadata::query()
            ->where('upstream_id', $upstream->id)
            ->delete();

        MirrorDist::query()
            ->where('upstream_id', $upstream->id)
            ->delete();

        DB::afterCommit(function () use ($upstream): void {
            Cache::forget("mirror:down:$upstream->id");
            Storage::deleteDirectory($upstream->storagePath());
        });
    }
}
