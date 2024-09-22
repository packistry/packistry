<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Import\Gitea;
use App\Enums\PackageSourceProvider;
use App\Models\PackageSource;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class ImportPackage extends Command
{
    public \App\Models\Repository $repository;

    /** @var string */
    protected $signature = 'app:import-package';

    /** @var string|null */
    protected $description = 'Add package source';

    public function __construct(private readonly Gitea $gitea)
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $this->selectRepository();
        $source = $this->selectPackageSource();

        $responses = match ($source->provider) {
            PackageSourceProvider::GITEA => $this->gitea->handle($this, $source),
            default => throw new Exception('not implemented')
        };

        if (is_null($responses)) {
            return self::FAILURE;
        }

        [$repository, $importedTags, $importedBranches] = $responses;

        $this->info("Imported $repository->name from $source->name");
        $this->table(['branches'], $importedBranches);
        $this->table(['tags'], $importedTags);

        return self::SUCCESS;
    }

    public function selectRepository(): void
    {
        $intoSub = confirm(
            label: 'Import into sub repository?',
            default: false,
        );

        $this->repository = \App\Models\Repository::query()
            ->whereNull('name')
            ->firstOrFail();

        if ($intoSub) {
            $repositories = \App\Models\Repository::query()
                ->whereNotNull('name')
                ->get()
                ->keyBy('id');

            $repositoryId = select(
                label: 'Select your sub repository',
                options: $repositories->map(fn (\App\Models\Repository $name): string => (string) $name->name),
                required: true,
            );

            $this->repository = $repositories[$repositoryId] ?? throw new Exception('selected repository not found');
        }
    }

    public function selectPackageSource(): PackageSource
    {
        /** @var Collection<int, PackageSource> $sources */
        $sources = PackageSource::query()
            ->get()
            ->keyBy('id');

        $sourceId = select(
            label: 'Select your package source',
            options: $sources->map(fn (PackageSource $source): string => $source->name)->toArray(),
            required: true,
        );

        /** @var PackageSource $source */
        $source = $sources[$sourceId];

        return $source;
    }
}
