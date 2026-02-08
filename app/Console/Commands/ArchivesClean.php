<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Archive;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ArchivesClean extends Command
{
    protected $signature = 'archives:clean {--dry-run : Preview changes without modifying anything}';

    protected $description = 'Remove archives that do not belong to any version';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $repositories = Repository::query()
            ->with('packages.versions')
            ->get();

        $deleted = 0;

        foreach ($repositories as $repository) {
            $this->info("Repository: $repository->name");

            $expectedPaths = $repository->packages
                ->map(fn (Package $package) => $package->setRelation('repository', $repository))
                ->flatMap(fn (Package $package) => $package->versions->map(
                    fn (Version $version) => Archive::name($package, $version->name)
                ))
                ->flip();

            $files = Storage::disk()->files($repository->path);

            foreach ($files as $file) {
                if ($file === '.gitignore') {
                    continue;
                }

                if (! $expectedPaths->has($file)) {
                    $this->line("  Removing: $file");

                    if (! $dryRun) {
                        Storage::delete($file);
                    }

                    $deleted++;
                }
            }
        }

        $this->newLine();
        $this->info("$deleted archive(s) ".($dryRun ? 'would be' : 'were').' removed.');

        if ($dryRun) {
            $this->warn('Dry run — no changes were made.');
        }

        return self::SUCCESS;
    }
}
