<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Packages\RebuildPackage;
use App\Models\Package;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multisearch;

class RebuildPackageCommand extends Command
{
    protected $signature = 'rebuild:package {--all : Rebuild all packages}';

    protected $description = 'Rebuild package tags and branches';

    public function handle(RebuildPackage $rebuildPackage): int
    {
        $all = $this->option('all');

        $packages = $this->packages(
            all: $all || confirm('Rebuild all packages?', false)
        );

        foreach ($packages as $package) {
            try {
                $rebuildPackage->handle($package);
            } catch (Throwable $exception) {
                $this->error($exception->getMessage());
            }
        }

        $this->info("Rebuilding {$packages->count()} packages");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Package>
     */
    private function packages(bool $all = false): Collection
    {
        return Package::query()
            ->when(! $all, fn (Builder $query) => $query->whereIn('id', multisearch(
                label: 'Select packages to rebuild',
                options: fn (string $value) => Package::query()
                    ->with('repository')
                    ->when($value, fn (Builder $query) => $query->where('name', 'like', "$value%"))
                    ->get()
                    ->keyBy(fn (Package $package): string => (string) $package->id)
                    ->map(fn (Package $package): string => "{$package->repository->name} > $package->name")
                    ->toArray(),
                required: true,
            )))
            ->with([
                'source',
            ])
            ->get();
    }
}
