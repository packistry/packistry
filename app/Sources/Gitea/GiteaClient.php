<?php

declare(strict_types=1);

namespace App\Sources\Gitea;

use App\Exceptions\InvalidTokenException;
use App\Models\Source;
use App\Normalizer;
use App\Sources\Branch;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use App\Sources\Traits\BearerToken;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\LazyCollection;
use RuntimeException;

class GiteaClient extends Client
{
    use BearerToken;

    /**
     * @throws ConnectionException|RequestException
     */
    public function projects(?string $search = null): array
    {
        $response = $this->http()->get('/api/v1/repos/search', [
            'q' => $search,
        ])->throw();

        /** @var array<string, mixed> $data */
        $data = $response->json()['data'];

        return array_map(fn (array $item): Project => new Project(
            id: $item['id'],
            fullName: $item['full_name'],
            name: $item['name'],
            url: $item['url'],
            webUrl: $item['html_url'],
            readOnly: $item['archived'],
        ), $data);
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function branches(Project $project): LazyCollection
    {
        return $this->lazy("$project->url/branches")
            ->map(fn (array $item): Branch => new Branch(
                id: (string) $project->id,
                name: $item['name'],
                url: Normalizer::url($project->webUrl),
                zipUrl: "$project->webUrl/archive/{$item['name']}.zip",
                sourceUrl: $project->webUrl,
                reference: $item['commit']['id'] ?? null,
            ));
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function tags(Project $project): LazyCollection
    {
        return $this->lazy("$project->url/tags")
            ->map(fn (array $item): Tag => new Tag(
                id: (string) $project->id,
                name: $item['name'],
                url: Normalizer::url($project->webUrl),
                zipUrl: $item['zipball_url'],
                sourceUrl: $project->webUrl,
                reference: $item['commit']['id'] ?? null,
            ));
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function createWebhook(\App\Models\Repository $repository, Project $project, Source $source): void
    {
        $this->http()->post("$project->url/hooks", [
            'type' => 'gitea',
            'config' => [
                'url' => $repository->url("/incoming/gitea/$source->id"),
                'secret' => decrypt($source->secret),
                'content_type' => 'json',
            ],
            'events' => ['push', 'delete'],
            'active' => true,
        ])->throw();
    }

    /**
     * @throws RequestException|ConnectionException
     */
    public function project(string $id): Project
    {
        $response = $this->http()->get("/api/v1/repositories/$id")->throw();

        /** @var array<string, mixed> $item */
        $item = $response->json();

        return new Project(
            id: $item['id'],
            fullName: $item['full_name'],
            name: $item['name'],
            url: $item['url'],
            webUrl: $item['html_url'],
            readOnly: $item['archived'],
        );
    }

    /**
     * @throws ConnectionException
     * @throws InvalidTokenException
     */
    public function validateToken(): void
    {
        try {
            $projects = $this->projects();
        } catch (Exception) {
            throw new InvalidTokenException(missingScopes: ['read:repository']);
        }

        if ($projects === []) {
            throw new InvalidTokenException(missingScopes: ['read:repository']);
        }

        $project = $projects[0];

        $response = $this->http()->post("$project->url/hooks");

        if ($response->status() === 422) {
            return;
        }

        throw new InvalidTokenException(missingScopes: ['write:repository']);
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
            $page = 1;

            while (true) {
                $response = $this->http()->get($uri, [
                    'page' => $page,
                ])->throw();

                $data = $response->json();

                if ($data === null) {
                    throw new RuntimeException($response->body());
                }

                foreach ($data as $item) {
                    yield $item;
                }

                if ($data === []) {
                    break;
                }

                $page += 1;
            }
        });
    }
}
