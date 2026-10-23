<?php

declare(strict_types=1);

namespace App\Actions\Upstreams;

use App\Actions\Upstreams\Inputs\UpdateUpstreamInput;
use App\Models\Upstream;
use Illuminate\Support\Facades\DB;
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
                $upstream->metadata_url = null;
                $upstream->root_checked_at = null;
            }

            $upstream->save();

            $this->syncCredentials->handle($upstream, $input->credentials);

            if ($upstream->isPrivate() && $upstream->repositories()->where('public', true)->exists()) {
                throw ValidationException::withMessages([
                    'credentials' => 'This upstream is attached to a public repository, it cannot authenticate against its own host.',
                ]);
            }

            return $upstream;
        });
    }
}
