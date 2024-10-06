<?php

declare(strict_types=1);

namespace App\Sources\Gitea;

use App\Models\Source;
use App\Normalizer;
use App\Sources\Branch;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use App\Sources\Traits\BearerToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
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
        ), $data);
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

        return array_map(fn (array $item): Branch => new Branch(
            id: (string) $project->id,
            name: $item['name'],
            url: Normalizer::url($project->webUrl),
            zipUrl: "$project->webUrl/archive/{$item['name']}.zip",
        ), $data);
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

        return array_map(fn (array $item): Tag => new Tag(
            id: (string) $project->id,
            name: $item['name'],
            url: Normalizer::url($project->webUrl),
            zipUrl: $item['zipball_url'],
        ), $data);
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function createWebhook(\App\Models\Repository $repository, Project $project, Source $source): void
    {
        $this->http()->post("$project->url/hooks", [
            'type' => 'gitea',
            'config' => [
                'url' => url($repository->url("/incoming/gitea/$source->id")),
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
        );
    }
}
