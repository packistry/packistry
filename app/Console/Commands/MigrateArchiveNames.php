<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Packages\RebuildPackage;
use App\Archive;
use App\Models\Version;
use App\Normalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateArchiveNames extends Command
{
    protected $signature = 'archive:migrate-names {--dry-run : Preview changes without modifying anything}';

    protected $description = 'Migrate archive names for versions with slashes and rebuild affected packages';

    public function handle(RebuildPackage $rebuildPackage): int
    {
        $dryRun = $this->option('dry-run');

        $affectedVersions = Version::query()
            ->whereRaw("name LIKE 'dev-%/%' OR name LIKE '%/%-dev'")
            ->with('package.repository', 'package.source')
            ->get();

        if ($affectedVersions->isEmpty()) {
            $this->info('No versions with slashes found.');

            return self::SUCCESS;
        }

        $this->info("Found {$affectedVersions->count()} version(s) with slashes.");

        $packagesToRebuild = collect();

        foreach ($affectedVersions as $version) {
            $package = $version->package;
            $normalized = Normalizer::version($version->name);

            // Old buggy path: basename() stripped everything before the last /
            $oldName = str_replace('/', '-', $package->name);
            $oldArchiveName = "$oldName-$normalized.zip";
            $oldPath = $package->repository->archivePath($oldArchiveName);

            // New correct path
            $newPath = Archive::name($package, $version->name);

            $this->line("  Version: $version->name (package: $package->name)");
            $this->line("    Old path: $oldPath");
            $this->line("    New path: $newPath");

            if (! $dryRun && Storage::exists($oldPath)) {
                Storage::delete($oldPath);
                $this->line('    Deleted old archive.');
            } elseif (! Storage::exists($oldPath)) {
                $this->warn('    Old archive not found on disk.');
            }

            $packagesToRebuild->put($package->id, $package);
        }

        $this->newLine();
        $this->info("Rebuilding {$packagesToRebuild->count()} affected package(s)...");

        foreach ($packagesToRebuild as $package) {
            if ($dryRun) {
                $this->line("  Would rebuild: $package->name");

                continue;
            }

            try {
                $rebuildPackage->handle($package);
                $this->line("  Rebuilding: $package->name");
            } catch (Throwable $exception) {
                $this->error("  Failed to rebuild $package->name: {$exception->getMessage()}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('Dry run — no changes were made.');
        }

        return self::SUCCESS;
    }
}
