<?php

declare(strict_types=1);

namespace App\Sources\Bitbucket;

use App\Exceptions\InvalidTokenException;
use App\Models\Repository;
use App\Models\Source;
use App\Sources\Branch;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use RuntimeException;

class BitbucketClient extends Client
{
    public function http(): PendingRequest
    {
        /** @var PendingRequest $request */
        $request = $this->requestOptions(Http::createPendingRequest());

        return $request;
    }

    private function requestOptions(PendingRequest|Pool $request): PendingRequest|Pool
    {
        return $request->baseUrl($this->url)
            ->withHeader('Authorization', 'Basic '.$this->token);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function projects(?string $search = null): array
    {
        $perPage = 100;
        $workspace = $this->workspace();

        $initialResponse = $this->http()->get("/2.0/repositories/$workspace", [
            'q' => $search !== null ? "name~\"$search\"" : null,
            'pagelen' => 1,
        ])->throw();

        $totalProjects = (int) ($initialResponse['size'] ?? 0);

        if ($totalProjects === 0) {
            return [];
        }

        $totalPages = ceil($totalProjects / $perPage);

        $responses = $this->http()
            ->pool(fn (Pool $pool): array => array_map(
                fn (float $page) => $this->requestOptions($pool)
                    ->get("/2.0/repositories/$workspace", [
                        'q' => $search !== null ? "name~\"$search\"" : null,
                        'pagelen' => $perPage,
                        'page' => $page,
                    ]), range(1, $totalPages)));

        $allProjects = [];

        foreach ($responses as $response) {
            if ($response instanceof \Throwable) {
                throw $response;
            }

            $response->throw();

            $data = $response->json();
            if (! isset($data['values'])) {
                throw new RuntimeException('Unexpected API response format.');
            }

            $projects = array_map(fn (array $item): Project => new Project(
                id: trim($item['uuid'], '{}'),
                fullName: $item['full_name'],
                name: $item['name'],
                url: $item['links']['self']['href'],
                webUrl: $item['links']['html']['href'],
            ), $data['values']);

            $allProjects = array_merge($allProjects, $projects);
        }

        return $allProjects;
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function branches(Project $project): LazyCollection
    {
        return $this->lazy("$project->url/refs/branches")
            ->map(fn (array $item): Branch => new Branch(
                id: (string) $project->id,
                name: $item['name'],
                url: $item['links']['html']['href'],
                zipUrl: "$project->webUrl/get/{$item['name']}.zip",
                sourceUrl: $project->webUrl,
                reference: $item['target']['hash'] ?? null,
            ));
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function tags(Project $project): LazyCollection
    {
        return $this->lazy("$project->url/refs/tags")
            ->map(fn (array $item): Tag => new Tag(
                id: (string) $project->id,
                name: $item['name'],
                url: $item['links']['html']['href'],
                zipUrl: "$project->webUrl/get/{$item['name']}.zip",
                sourceUrl: $project->webUrl,
                reference: $item['target']['hash'] ?? null,
            ));
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function createWebhook(Repository $repository, Project $project, Source $source): void
    {
        $this->http()->post("$project->url/hooks", [
            'description' => 'Packistry sync',
            'url' => $repository->url("/incoming/bitbucket/$source->id"),
            'active' => true,
            'secret' => decrypt($source->secret),
            'events' => [
                'repo:push',
            ],
        ])->throw();
    }

    /**
     * @throws ConnectionException
     */
    public function project(string $id): Project
    {
        $url = "/2.0/repositories/{$this->workspace()}";

        $response = $this->http()->get($url, [
            'q' => "uuid=\"$id\"",
        ]);

        $item = $response->json();
        $item = $item['values'][0] ?? $item;

        return new Project(
            id: trim($item['uuid'], '{}'),
            fullName: $item['full_name'],
            name: $item['name'],
            url: $item['links']['self']['href'],
            webUrl: $item['links']['html']['href'],
        );
    }

    /**
     * @throws InvalidTokenException|ConnectionException
     */
    public function validateToken(): void
    {
        try {
            $projects = $this->projects();
        } catch (Exception) {
            throw new InvalidTokenException(missingScopes: ['read:repository', 'write:repository', 'admin:repository']);
        }

        if ($projects === []) {
            throw new InvalidTokenException(missingScopes: ['read:repository', 'write:repository', 'admin:repository']);
        }

        $project = $projects[0];

        $response = $this->http()->post("$project->url/hooks", [
            'events' => [],
        ]);

        if ($response->status() === 403) {
            throw new InvalidTokenException(
                missingScopes: ['write:webhook']
            );
        }
    }

    protected function workspace(): string
    {
        $workspace = $this->metadata['workspace'] ?? null;

        return $workspace !== null && $workspace !== '' ? $workspace : '';
    }

    /**
     * @noinspection PhpDocRedundantThrowsInspection
     *
     * @return LazyCollection<array-key, array<string, mixed>>
     *
     * @throws ConnectionException|RequestException
     */
    private function lazy(string $uri): LazyCollection
    {
        return LazyCollection::make(function () use ($uri) {
            $perPage = 100;
            $page = 1;

            do {
                $response = $this->http()->get($uri, [
                    'pagelen' => $perPage,
                    'page' => $page,
                ])->throw();

                $data = $response->json();

                if (! isset($data['values'])) {
                    throw new RuntimeException('Unexpected API response format.');
                }

                foreach ($data['values'] as $item) {
                    yield $item;
                }

                $page++;
            } while (array_key_exists('next', $data) && is_string($data['next']));
        });
    }
}
