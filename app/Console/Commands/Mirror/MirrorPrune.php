<?php

declare(strict_types=1);

namespace App\Console\Commands\Mirror;

use App\Models\MirrorDist;
use App\Models\MirrorMetadata;
use App\Models\Upstream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MirrorPrune extends Command
{
    protected $signature = 'mirror:prune
        {--unused-for=90 : Remove cached dists and metadata that have not been used for this many days}
        {--dry-run : Preview changes without modifying anything}';

    protected $description = 'Remove cached upstream dists and metadata that are no longer used';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run') === true;
        $days = (int) $this->option('unused-for');

        if ($days < 1) {
            $this->error('--unused-for must be at least 1 day.');

            return self::FAILURE;
        }

        $before = now()->subDays($days);

        $dists = 0;
        $bytes = 0;

        MirrorDist::query()
            ->where(fn ($query) => $query
                ->where('last_accessed_at', '<', $before)
                ->orWhere(fn ($query) => $query->whereNull('last_accessed_at')->where('created_at', '<', $before)))
            ->chunkById(500, function ($chunk) use ($dryRun, &$dists, &$bytes): void {
                /** @var MirrorDist $dist */
                foreach ($chunk as $dist) {
                    $this->line("  Dist: $dist->name ($dist->version)", verbosity: 'v');

                    $dists++;
                    $bytes += $dist->size ?? 0;

                    if (! $dryRun) {
                        Storage::delete($dist->archive_path);
                        $dist->delete();
                    }
                }
            });

        $metadata = 0;

        MirrorMetadata::query()
            ->where(fn ($query) => $query
                ->where('checked_at', '<', $before)
                ->orWhereNull('checked_at'))
            ->where('updated_at', '<', $before)
            ->chunkById(500, function ($chunk) use ($dryRun, &$metadata): void {
                /** @var MirrorMetadata $cached */
                foreach ($chunk as $cached) {
                    $this->line("  Metadata: $cached->name".($cached->dev ? ' (dev)' : ''), verbosity: 'v');

                    $metadata++;

                    if (! $dryRun) {
                        Storage::delete($cached->body_path);
                        $cached->delete();
                    }
                }
            });

        $orphans = $this->pruneOrphans($dryRun);

        $verb = $dryRun ? 'would be' : 'were';

        $this->info(sprintf('%d dist(s) (%s) %s removed.', $dists, $this->formatBytes($bytes), $verb));
        $this->info("$metadata metadata file(s) $verb removed.");
        $this->info("$orphans orphaned file(s) $verb removed.");

        if ($dryRun) {
            $this->warn('Dry run — no changes were made.');
        }

        return self::SUCCESS;
    }

    /**
     * Files in the mirror storage that no database row refers to, e.g. of deleted upstreams.
     */
    private function pruneOrphans(bool $dryRun): int
    {
        $upstreamIds = Upstream::query()->pluck('id')->map(fn (int $id): string => (string) $id)->all();
        $removed = 0;

        foreach (Storage::directories('_mirror') as $directory) {
            if (in_array(basename($directory), $upstreamIds, true)) {
                continue;
            }

            $removed += count(Storage::allFiles($directory));

            if (! $dryRun) {
                Storage::deleteDirectory($directory);
            }
        }

        $known = MirrorDist::query()->pluck('archive_path')
            ->merge(MirrorMetadata::query()->pluck('body_path'))
            ->flip();

        foreach ($upstreamIds as $upstreamId) {
            foreach (Storage::allFiles("_mirror/$upstreamId") as $file) {
                if ($known->has($file)) {
                    continue;
                }

                $removed++;

                if (! $dryRun) {
                    Storage::delete($file);
                }
            }
        }

        return $removed;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }
}
