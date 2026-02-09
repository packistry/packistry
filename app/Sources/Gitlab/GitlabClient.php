<?php

declare(strict_types=1);

namespace App\Sources\Gitlab;

use App\Exceptions\InvalidTokenException;
use App\Models\Repository;
use App\Models\Source;
use App\Sources\Branch;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use RuntimeException;

class GitlabClient extends Client
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
            ->withHeader('Private-Token', $this->token);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function projects(?string $search = null): array
    {
        $response = $this->http()->get('/api/v4/projects', [
            'per_page' => 100,
            'search' => $search,
            'search_namespaces' => ! str_starts_with('https://gitlab.com', $this->url), // throws 500 on gitlab.com?
        ])->throw();

        $response->throw();

        $data = $response->json();

        return array_map(fn (array $item): Project => new Project(
            id: $item['id'],
            fullName: $item['path_with_namespace'],
            name: $item['name'],
            url: $item['_links']['self'],
            webUrl: $item['web_url'],
            readOnly: $item['archived'],
        ), $data);
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function branches(Project $project): LazyCollection
    {
        return $this->lazy("$project->url/repository/branches")
            ->map(fn (array $branch): Branch => new Branch(
                id: (string) $project->id,
                name: $branch['name'],
                url: $project->url,
                zipUrl: "$project->url/repository/archive.zip?sha={$branch['commit']['id']}",
            ));
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function tags(Project $project): LazyCollection
    {
        return $this->lazy("$project->url/repository/tags")
            ->map(fn (array $tag): Tag => new Tag(
                id: (string) $project->id,
                name: $tag['name'],
                url: $project->url,
                zipUrl: "$project->url/repository/archive.zip?sha={$tag['commit']['id']}",
            ));
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function createWebhook(Repository $repository, Project $project, Source $source): void
    {
        $this->http()->post("$project->url/hooks", [
            'url' => $repository->url("/incoming/gitlab/$source->id"),
            'name' => 'Packistry sync',
            'token' => decrypt($source->secret),
            'content_type' => 'json',
            'tag_push_events' => true,
            'branch_push_events' => true,
        ])->throw();
    }

    /**
     * @throws RequestException|ConnectionException
     */
    public function project(string $id): Project
    {
        $response = $this->http()->get("/api/v4/projects/$id")->throw();
        $item = $response->json();

        return new Project(
            id: $item['id'],
            fullName: $item['path_with_namespace'],
            name: $item['name'],
            url: $item['_links']['self'],
            webUrl: $item['web_url'],
            readOnly: $item['archived'],
        );
    }

    /**
     * @throws InvalidTokenException|ConnectionException
     */
    public function validateToken(): void
    {
        $response = $this->http()
            ->get('/api/v4/personal_access_tokens/self');

        $json = $response->json();

        if (
            ! is_array($json)
            || ! array_key_exists('scopes', $json)
            || ! in_array('api', $json['scopes'], true)) {
            throw new InvalidTokenException(
                missingScopes: ['api']
            );
        }
    }

    /**
     * @noinspection PhpDocRedundantThrowsInspection
     *
     * @return LazyCollection<array-key, array<string, mixed>>
     *
     * @throws ConnectionException|RequestException
     */
    private function lazy(string $url): LazyCollection
    {
        return LazyCollection::make(function () use ($url) {
            $perPage = 100;
            $page = 1;

            do {
                $response = $this->http()->get($url, [
                    'per_page' => $perPage,
                    'page' => $page,
                ])->throw();

                $data = $response->json();

                if ($data === null) {
                    throw new RuntimeException($response->body());
                }

                foreach ($data as $item) {
                    yield $item;
                }

                $page++;
            } while ($response->header('x-next-page'));
        });
    }
}
