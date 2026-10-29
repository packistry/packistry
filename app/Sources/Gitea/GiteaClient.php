<?php

declare(strict_types=1);

namespace App\Sources\Gitea;

use App\Exceptions\InvalidTokenException;
use App\Models\Repository;
use App\Models\Source;
use App\Normalizer;
use App\Sources\Branch;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use App\Sources\Traits\BearerToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
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
    public function createWebhook(Repository $repository, Project $project, Source $source): void
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
     * Gitea has no token introspection endpoint, but it checks a token's scopes before
     * it looks up the repository. Probing a repository that doesn't exist therefore
     * returns 404 only when the token passed authentication and the scope check.
     *
     * @throws ConnectionException
     * @throws InvalidTokenException
     */
    public function validateToken(): void
    {
        // This route requires a signed-in token, so a missing or invalid token gets a 401
        $read = $this->http()->get('/api/v1/repositories/0');

        if (! $read->notFound()) {
            throw new InvalidTokenException(missingScopes: ['read:repository']);
        }

        $nonexistentRepository = 'packistry-token-check/'.Str::random(32);

        $write = $this->http()->post("/api/v1/repos/$nonexistentRepository/hooks");

        if (! $write->notFound()) {
            throw new InvalidTokenException(missingScopes: ['write:repository']);
        }
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
