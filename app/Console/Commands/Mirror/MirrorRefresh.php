<?php

declare(strict_types=1);

namespace App\Console\Commands\Mirror;

use App\Mirror\MetadataCache;
use App\Models\MirrorMetadata;
use App\Models\Upstream;
use Illuminate\Console\Command;

class MirrorRefresh extends Command
{
    protected $signature = 'mirror:refresh
        {package? : Only refresh this package, e.g. acme/package}
        {--upstream= : Only refresh metadata of this upstream (id or name)}';

    protected $description = 'Fetch cached upstream metadata again, so new versions show up before the cache expires';

    public function handle(MetadataCache $metadata): int
    {
        $package = $this->argument('package');
        $upstreamOption = $this->option('upstream');

        $upstreams = Upstream::query()
            ->with('credentials')
            ->when(is_string($upstreamOption), fn ($query) => $query
                ->where('id', $upstreamOption)
                ->orWhere('name', $upstreamOption))
            ->get()
            ->keyBy('id');

        if (is_string($upstreamOption) && $upstreams->isEmpty()) {
            $this->error("Upstream [$upstreamOption] not found.");

            return self::FAILURE;
        }

        $cached = MirrorMetadata::query()
            ->whereIn('upstream_id', $upstreams->keys())
            ->when(is_string($package), fn ($query) => $query->where('name', $package))
            ->orderBy('name')
            ->get();

        if ($cached->isEmpty()) {
            $this->warn('Nothing to refresh, no matching metadata is cached.');

            return self::SUCCESS;
        }

        $counts = ['refreshed' => 0, 'removed upstream' => 0, 'unavailable' => 0];

        foreach ($cached as $row) {
            /** @var Upstream $upstream */
            $upstream = $upstreams->get($row->upstream_id);
            $result = $metadata->refresh($upstream, $row->name, $row->dev);
            $label = "$row->name".($row->dev ? ' (dev)' : '')." [$upstream->name]";

            if ($result->isFound() && ! $result->stale) {
                $counts['refreshed']++;
                $this->line("  <info>✓</info> $label", verbosity: 'v');
            } elseif ($result->isNotFound()) {
                $counts['removed upstream']++;
                $this->line("  <comment>-</comment> $label no longer exists upstream");
            } else {
                $counts['unavailable']++;
                $this->line("  <error>✗</error> $label could not be fetched, keeping the cached copy");
            }
        }

        $this->table(['Result', 'Count'], collect($counts)->map(fn (int $count, string $result): array => [$result, $count])->values()->all());

        return $counts['unavailable'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
