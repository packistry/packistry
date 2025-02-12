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
        $perPage = 100;

        $initialResponse = $this->http()->get('/api/v4/projects', [
            'per_page' => 1,
            'search' => $search,
            'search_namespaces' => true,
        ])->throw();

        $totalProjects = (int) $initialResponse->header('X-Total-Pages');
        $totalPages = ceil($totalProjects / $perPage);

        $responses = $this->http()
            ->pool(fn (Pool $pool): array => array_map(
                fn (float $page) => $this->requestOptions($pool)
                    ->get('/api/v4/projects', [
                        'page' => $page,
                        'per_page' => $perPage,
                        'search' => $search,
                    ]), range(1, $totalPages)));

        $allProjects = [];

        foreach ($responses as $response) {
            $response->throw();

            $data = $response->json();
            $projects = array_map(fn (array $item): Project => new Project(
                id: $item['id'],
                fullName: $item['path_with_namespace'],
                name: $item['name'],
                url: $item['_links']['self'].'/repository',
                webUrl: $item['web_url'],
            ), $data);

            $allProjects = array_merge($allProjects, $projects);
        }

        return $allProjects;
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function branches(Project $project): array
    {
        $response = $this->http()->get("$project->url/branches")->throw();

        $data = $response->json();

        if (is_null($data)) {
            new RuntimeException($response->getBody()->getContents());
        }

        return array_map(function (array $item) use ($project): Branch {
            $sha = $item['commit']['id'];

            return new Branch(
                id: (string) $project->id,
                name: $item['name'],
                url: $project->url,
                zipUrl: "$project->url/archive.zip?sha=$sha",
            );
        }, $data);
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function tags(Project $project): array
    {
        $response = $this->http()->get("$project->url/tags")->throw();

        $data = $response->json();

        if (is_null($data)) {
            new RuntimeException($response->getBody()->getContents());
        }

        return array_map(function (array $item) use ($project): Tag {
            $sha = $item['commit']['id'];

            return new Tag(
                id: (string) $project->id,
                name: $item['name'],
                url: $project->url,
                zipUrl: "$project->url/archive.zip?sha=$sha",
            );
        }, $data);
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
            url: $item['_links']['self'].'/repository',
            webUrl: $item['web_url'],
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
}
