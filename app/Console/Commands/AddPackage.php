<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PackageType;
use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\VersionNotFoundException;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Source;
use App\Sources\Client;
use App\Sources\Importable;
use App\Sources\Project;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class AddPackage extends Command
{
    /** @var string */
    protected $signature = 'conductor:add:package';

    /** @var string|null */
    protected $description = 'Add a package from one of your sources';

    public Repository $repository;

    public Project $project;

    private Client $client;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $this->selectRepository();
        $source = $this->selectSource();

        $this->client = $source->client();

        $this->selectProjects()
            ->each(function (Project $project) use ($source): void {
                $package = Package::query()
                    ->where('name', $project->fullName)
                    ->first() ?? new Package;

                $package->repository_id = $this->repository->id;
                $package->source_id = $source->id;
                $package->name = $project->fullName;
                $package->type = PackageType::LIBRARY;

                $package->save();

                $tags = $this->importTags($project);
                $branches = $this->importBranches($project);
                $this->createWebhook($project);

                $this->info("Imported $project->fullName from $source->name");
                $this->table(['tags'], $tags);
                $this->table(['branches'], $branches);
            });

        return self::SUCCESS;
    }

    /**
     * @throws Exception
     */
    public function selectRepository(): void
    {
        $intoRoot = confirm(
            label: 'Import into root repository?',
            default: true,
        );

        $this->repository = Repository::query()
            ->whereNull('name')
            ->firstOrFail();

        if (! $intoRoot) {
            $repositories = Repository::query()
                ->whereNotNull('name')
                ->get()
                ->keyBy('id');

            $repositoryId = select(
                label: 'Select your sub repository',
                options: $repositories->map(fn (Repository $name): string => (string) $name->name),
                required: true,
            );

            $this->repository = $repositories[$repositoryId] ?? throw new Exception('selected repository not found');
        }
    }

    public function selectSource(): Source
    {
        /** @var Collection<int, Source> $sources */
        $sources = Source::query()
            ->get()
            ->keyBy('id');

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
     * @return Collection<int, Project>
     */
    private function selectProjects(): Collection
    {
        $projects = collect($this->client->projects())
            ->keyBy(fn (Project $project): int|string => $project->id)
            ->map(fn (Project $project): Project => $project);

        $projectIds = multiselect(
            label: 'Select projects to import',
            options: $projects->map(fn (Project $project): string => $project->fullName)->toArray(),
            required: true,
        );

        $projectIds = array_flip($projectIds);

        return $projects->filter(fn (Project $project): bool => array_key_exists($project->id, $projectIds));
    }

    /**
     * @return string[][]
     *
     * @throws ArchiveInvalidContentTypeException
     * @throws ConnectionException
     * @throws FailedToFetchArchiveException
     * @throws VersionNotFoundException
     */
    private function importTags(Project $project): array
    {
        return $this->importAll(
            $this->client->tags($project)
        );
    }

    /**
     * @return string[][]
     *
     * @throws ArchiveInvalidContentTypeException
     * @throws ConnectionException
     * @throws FailedToFetchArchiveException
     * @throws VersionNotFoundException
     */
    private function importBranches(Project $project): array
    {
        return $this->importAll(
            $this->client->branches($project)
        );
    }

    private function createWebhook(Project $project): void
    {
        $this->client->createWebhook($this->repository, $project);
    }

    /**
     * @param  Importable[]  $imports
     * @return array<int, array{string}>
     *
     * @throws VersionNotFoundException
     * @throws FailedToFetchArchiveException
     * @throws ConnectionException
     * @throws ArchiveInvalidContentTypeException
     */
    private function importAll(array $imports): array
    {
        return array_map(function (Importable $tag): array {
            try {
                $version = $this->client->import(
                    repository: $this->repository,
                    importable: $tag,
                );
            } catch (ComposerJsonNotFoundException) {
                return ["{$tag->version()}: failed, composer.json is missing"];
            }

            return [$version->name];
        }, $imports);
    }
}
