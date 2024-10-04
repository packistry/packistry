<?php

declare(strict_types=1);

namespace App\Console\Commands\Delete;

use App\Actions\Sources\DestroySource;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multisearch;

class DeleteSource extends Command
{
    /** @var string */
    protected $signature = 'delete:source';

    /** @var string|null */
    protected $description = 'Delete a source';

    public function handle(DestroySource $destroySource): int
    {
        $sources = multisearch(
            label: 'Select the users to delete',
            options: fn (string $value) => Source::query()
                ->when($value, fn (Builder $query) => $query->where('name', 'like', "$value%"))
                ->get()
                ->keyBy(fn (Source $source): string => (string) $source->id)
                ->map(fn (Source $source): string => $source->name)
                ->toArray(),
            required: true,
        );

        if (! confirm('Do you really want to delete the selected sources?', default: false)) {
            return self::FAILURE;
        }

        foreach ($sources as $source) {
            /** @var Source $source */
            $source = Source::query()->findOrFail($source);
            $destroySource->handle($source);

            $this->info("Source $source->name deleted");
        }

        return self::SUCCESS;
    }
}
