<?php

declare(strict_types=1);

namespace App\Console\Commands\Delete;

use App\Actions\Repositories\DestroyRepository;
use App\Models\Repository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multisearch;

class DeleteRepository extends Command
{
    /** @var string */
    protected $signature = 'packistry:delete:repository';

    /** @var string|null */
    protected $description = 'Delete a repository';

    public function handle(DestroyRepository $destroyRepository): int
    {
        $repositories = multisearch(
            label: 'Select the repository to delete',
            options: fn (string $value) => Repository::query()
                ->when($value, fn (Builder $query) => $query->where('name', 'like', "$value%"))
                ->get()
                ->keyBy(fn (Repository $repository): string => (string) $repository->id)
                ->map(fn (Repository $repository): string => $repository->name ?? 'Root')
                ->toArray(),
            required: true,
        );

        if (! confirm('Do you really want to delete the selected repositories?', default: false)) {
            return self::FAILURE;
        }

        foreach ($repositories as $repository) {
            /** @var Repository $repository */
            $repository = Repository::query()->findOrFail($repository);
            $destroyRepository->handle($repository);

            $this->info("Repository $repository->name deleted");
        }

        return self::SUCCESS;
    }
}
