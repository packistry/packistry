<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\ConstraintValidator;
use App\Models\Version;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VersionsClean extends Command
{
    protected $signature = 'versions:clean {--dry-run : Preview changes without modifying anything}';

    protected $description = 'Remove versions with version constraints Composer cannot parse';

    public function handle(ConstraintValidator $constraintValidator): int
    {
        $dryRun = $this->option('dry-run') === true;

        $versions = Version::query()
            ->with('package')
            ->lazyById();

        $deleted = 0;

        foreach ($versions as $version) {
            $invalidConstraints = $constraintValidator->invalidConstraints($version->metadata);

            if ($invalidConstraints === []) {
                continue;
            }

            $links = implode(', ', $invalidConstraints);

            $this->line("  Removing: {$version->package->name} $version->name ($links)");

            if (! $dryRun) {
                $this->delete($version);
            }

            $deleted++;
        }

        $this->newLine();
        $this->info("$deleted version(s) ".($dryRun ? 'would be' : 'were').' removed.');

        if ($dryRun) {
            $this->warn('Dry run — no changes were made.');
        }

        return self::SUCCESS;
    }

    private function delete(Version $version): void
    {
        if ($version->archive_path !== null) {
            Storage::disk()->delete($version->archive_path);
        }

        $version->delete();
    }
}
