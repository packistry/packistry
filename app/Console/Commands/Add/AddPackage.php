<?php

declare(strict_types=1);

namespace App\Console\Commands\Add;

use App\Actions\Packages\Inputs\StorePackageInput;
use App\Actions\Packages\StorePackage;
use App\Models\Repository;
use App\Models\Source;
use App\Sources\Client;
use App\Sources\Project;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

use function Laravel\Prompts\multisearch;
use function Laravel\Prompts\select;

class AddPackage extends Command
{
    protected $signature = 'add:package';

    protected $description = 'Add a package from one of your sources';

    public Repository $repository;

    public Project $project;

    private Client $client;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Throwable
     */
    public function handle(StorePackage $storePackage): int
    {
        $this->selectRepository();
        $source = $this->selectSource();
        $this->client = $source->client();

        $projects = $this->selectProjects();

        $webhook = $this->confirm('Create webhook?', true);

        $packages = $storePackage->handle(new StorePackageInput(
            repository: (string) $this->repository->id,
            source: (string) $source->id,
            projects: $projects,
            webhook: $webhook
        ));

        foreach ($packages as $package) {
            $this->info($package->name);
        }

        return self::SUCCESS;
    }

    /**
     * @throws Exception
     */
    public function selectRepository(): void
    {
        $repositories = Repository::query()
            ->get()
            ->keyBy(fn (Repository $repository): int => $repository->id);

        $repositoryId = select(
            label: 'Select your sub repository',
            options: $repositories->map(fn (Repository $name): string => $name->name)->toArray(),
            required: true,
        );

        $this->repository = $repositories[$repositoryId] ?? throw new Exception('selected repository not found');
    }

    public function selectSource(): Source
    {
        /** @var Collection<int, Source> $sources */
        $sources = Source::query()
            ->get()
            ->keyBy(fn (Source $source): int => $source->id);

        $sourceId = select(
            label: 'Select your package source',
            options: $sources->map(fn (Source $source): string => $source->name)->toArray(),
            required: true,
        );

        /** @var Source $source */
        $source = $sources[$sourceId];

        return $source;
    }

    /**
     * @return string[]
     */
    private function selectProjects(): array
    {
        /** @var string[] $projects */
        $projects = multisearch(
            label: 'Select projects to import',
            options: function (string $value) {
                if (strlen($value) <= 3) {
                    return [];
                }

                return collect($this->client->projects($value))
                    ->keyBy(fn (Project $project): string => (string) $project->id)
                    ->map(fn (Project $project): string => $project->fullName)
                    ->toArray();
            },
            placeholder: 'Type at least 4 characters',
            required: true,
        );

        return $projects;
    }
}
