<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Package;
use App\Models\Version;
use DB;
use Illuminate\Console\Command;

class RecalculateTotalDownloads extends Command
{
    protected $signature = 'recalculate:total-downloads';

    protected $description = 'Recalculate package and version total downloads';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Package::query()
            ->update(['total_downloads' => DB::raw('(select count(*) from downloads where package_id = packages.id)')]);

        Version::query()
            ->update(['total_downloads' => DB::raw('(select count(*) from downloads where version_id = versions.id)')]);

        return self::SUCCESS;
    }
}
