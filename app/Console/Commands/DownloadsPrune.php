<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Download;
use Illuminate\Console\Command;

class DownloadsPrune extends Command
{
    protected $signature = 'downloads:prune
        {--older-than=365 : Remove download records older than this many days}
        {--dry-run : Preview changes without modifying anything}';

    protected $description = 'Remove old download records, total download counters are kept';

    public function handle(): int
    {
        $days = (int) $this->option('older-than');

        if ($days < 90) {
            $this->error('--older-than must be at least 90 days, the download charts show the last 90 days.');

            return self::FAILURE;
        }

        $query = Download::query()->where('created_at', '<', now()->subDays($days)->startOfDay());

        if ($this->option('dry-run') === true) {
            $this->info($query->count().' download record(s) would be removed.');
            $this->warn('Dry run — no changes were made.');

            return self::SUCCESS;
        }

        $removed = 0;

        do {
            $ids = (clone $query)->limit(5000)->pluck('id');
            $removed += Download::query()->whereIn('id', $ids)->delete();
        } while ($ids->isNotEmpty());

        $this->info("$removed download record(s) were removed.");
        $this->comment('Package and version totals are unchanged, do not run recalculate:total-downloads afterwards as it counts the remaining records.');

        return self::SUCCESS;
    }
}
