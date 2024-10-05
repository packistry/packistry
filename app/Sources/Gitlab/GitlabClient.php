<?php

declare(strict_types=1);

namespace App\Sources\Gitlab;

use App\Models\Repository;
use App\Sources\Branch;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
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
     */
    public function projects(?string $search = null): array
    {
        $perPage = 100;

        $initialResponse = $this->http()->get('/api/v4/projects', [
            'per_page' => 1,
            'search' => $search,
            'search_namespaces' => true,
        ]);

        if (! $initialResponse->successful()) {
            throw new \Exception('Failed to fetch initial project data: '.$initialResponse->body());
        }

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
            if (! $response->successful()) {
                throw new \Exception('Failed to fetch projects: '.$response->body());
            }

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
     * @throws ConnectionException
     */
    public function branches(Project $project): array
    {
        $response = $this->http()->get("$project->url/branches");

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
     * @throws ConnectionException
     */
    public function tags(Project $project): array
    {
        $response = $this->http()->get("$project->url/tags");

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
     * @throws ConnectionException
     */
    public function createWebhook(Repository $repository, Project $project): void
    {
        $this->http()->post("$project->url/hooks", [
            'url' => url($repository->url('/incoming/gitlab')),
            'name' => 'packistry sync',
            // @todo remove secret and generate it instead on source creation?
            'token' => config('services.gitea.webhook.secret'),
            'content_type' => 'json',
            'tag_push_events' => true,
            'branch_push_events' => true,
        ]);
    }

    public function project(string $id): Project
    {
        $response = $this->http()->get("/api/v4/projects/$id");
        $item = $response->json();

        return new Project(
            id: $item['id'],
            fullName: $item['path_with_namespace'],
            name: $item['name'],
            url: $item['_links']['self'].'/repository',
            webUrl: $item['web_url'],
        );
    }
}
