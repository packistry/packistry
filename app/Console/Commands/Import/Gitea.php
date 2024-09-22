<?php

declare(strict_types=1);

namespace App\Console\Commands\Import;

use App\Console\Commands\ImportPackage;
use App\Enums\PackageType;
use App\Import;
use App\Incoming\Gitea\Branch;
use App\Incoming\Gitea\Repository;
use App\Incoming\Gitea\Tag;
use App\Incoming\Importable;
use App\Models\Package;
use App\Models\PackageSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class Gitea
{
    private PendingRequest $http;

    private Repository $repository;

    public function __construct(private readonly Import $import)
    {
        //
    }

    /**
     * @return array{Repository, string[][], string[][]}|null
     */
    public function handle(ImportPackage $command, PackageSource $source): ?array
    {
        $token = decrypt($source->token);

        $this->http = Http::withHeader('Authorization', "Bearer $token");

        $this->repository = $this->searchSourceRepository($source);

        if (Package::query()->where('name', $this->repository->fullName)->exists()) {
            $command->error('Package already exists');

            return null;
        }

        $package = new Package;

        $package->repository_id = $command->repository->id;
        $package->source_id = $source->id;
        $package->name = $this->repository->fullName;
        $package->type = PackageType::LIBRARY;

        $package->save();

        $this->http->baseUrl($this->repository->url);

        $tags = $this->importTags($command);
        $branches = $this->importBranches($command);
        $this->createWebhook($command);

        return [$this->repository, $tags, $branches];
    }

    private function searchSourceRepository(PackageSource $source): Repository
    {
        $response = $this->http->get("$source->url/api/v1/repos/search");

        /** @var array<string, mixed> $data */
        $data = $response->json()['data'];

        /** @var Collection<int, Repository> $repositories */
        $repositories = collect($data)
            ->keyBy('id')
            ->map(fn (array $item): Repository => Repository::from($item));

        $repositoryId = select(
            label: 'Select your source repository',
            options: $repositories->map(fn (Repository $repository): string => $repository->name)->toArray(),
            required: true,
        );

        return $repositories[$repositoryId] ?? throw new RuntimeException;
    }

    /**
     * @return string[][]
     *
     * @throws ConnectionException
     */
    private function importTags(ImportPackage $command): array
    {
        if (! confirm(label: 'Import tags?', default: true)) {
            return [];
        }

        $command->info('Importing tags');

        $response = $this->http->get('tags');
        /** @var array<string, mixed> $data */
        $data = $response->json();

        return collect($data)
            ->map(fn (array $item): Tag => Tag::from([...$item, 'repository' => $this->repository]))
            ->map(function (Importable $tag) use ($command): array {
                $version = $this->import->import($command->repository, $tag, $this->http);

                return [$version->name];
            })->toArray();
    }

    /**
     * @return string[][]
     *
     * @throws ConnectionException
     */
    private function importBranches(ImportPackage $command): array
    {
        if (! confirm(label: 'Import branches?', default: true)) {
            return [];
        }

        $response = $this->http->get('branches');
        /** @var array<string, mixed> $data */
        $data = $response->json();

        $command->info('Importing branches');

        return collect($data)
            ->map(fn (array $item): Branch => Branch::from([...$item, 'repository' => $this->repository]))
            ->map(function (Importable $tag) use ($command): array {
                $version = $this->import->import($command->repository, $tag, $this->http);

                return [$version->name];
            })->toArray();
    }

    private function createWebhook(ImportPackage $command): void
    {
        if (! confirm(label: 'Create webhook?', default: true)) {
            return;
        }

        $command->info('Creating webhook');

        $this->http->post('hooks', [
            'type' => 'gitea',
            'config' => [
                'url' => url('incoming/gitea'),
                'secret' => config('services.gitea.webhook.secret'),
                'content_type' => 'json',
            ],
            'events' => ['push', 'delete'],
            'active' => true,
        ]);
    }
}
