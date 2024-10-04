<?php

declare(strict_types=1);

namespace App\Console\Commands\Delete;

use App\Actions\Packages\DestroyPackage;
use App\Models\Package;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multisearch;

class DeletePackage extends Command
{
    /** @var string */
    protected $signature = 'packistry:delete:package';

    /** @var string|null */
    protected $description = 'Delete a package';

    public function handle(DestroyPackage $destroyPackage): int
    {
        $packages = multisearch(
            label: 'Select the packages to delete',
            options: fn (string $value) => Package::query()
                ->with('repository')
                ->when($value, fn (Builder $query) => $query->where('name', 'like', "$value%"))
                ->get()
                ->keyBy(fn (Package $package): string => (string) $package->id)
                ->map(function (Package $package): string {
                    $repository = $package->repository->name ?? 'Root';

                    return "[$repository] $package->name";
                })
                ->toArray(),
            required: true,
        );

        if (! confirm('Do you really want to delete the selected packages?', default: false)) {
            return self::FAILURE;
        }

        foreach ($packages as $package) {
            /** @var Package $package */
            $package = Package::query()->findOrFail($package);
            $destroyPackage->handle($package);

            $this->info("Package $package->name deleted");
        }

        return self::SUCCESS;
    }
}
