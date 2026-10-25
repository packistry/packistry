<?php

declare(strict_types=1);

namespace App\Actions\Upstreams;

use App\Actions\Upstreams\Inputs\RepositoryUpstreamInput;
use App\Actions\Upstreams\Inputs\SyncRepositoryUpstreamsInput;
use App\Enums\MirrorStatus;
use App\Models\Repository;
use App\Models\Upstream;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SyncRepositoryUpstreams
{
    /**
     * @throws Throwable
     */
    public function handle(Repository $repository, SyncRepositoryUpstreamsInput $input): Repository
    {
        $ids = array_map(fn (RepositoryUpstreamInput $upstream): int => $upstream->id, $input->upstreams);

        if ($repository->public) {
            $private = Upstream::query()
                ->with('credentials')
                ->whereIn('id', $ids)
                ->get()
                ->filter(fn (Upstream $upstream): bool => $upstream->isPrivate());

            if ($private->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'upstreams' => 'Public repositories cannot use authenticated upstreams: '
                        .$private->pluck('name')->implode(', '),
                ]);
            }
        }

        DB::transaction(function () use ($repository, $input): void {
            $sync = [];

            foreach (array_values($input->upstreams) as $priority => $upstream) {
                $sync[$upstream->id] = [
                    'priority' => $priority,
                    'enabled' => $upstream->enabled,
                ];
            }

            $repository->upstreams()->sync($sync);

            $enabled = array_keys(array_filter($sync, fn (array $pivot): bool => $pivot['enabled']));

            // packages of detached or disabled upstreams resolve again on the next request
            $repository->packages()
                ->whereNotNull('upstream_id')
                ->whereNotIn('upstream_id', $enabled)
                ->update(['mirror_status' => MirrorStatus::GONE->value]);
        });

        return $repository->load('upstreams');
    }
}
