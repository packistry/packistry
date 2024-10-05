<?php

declare(strict_types=1);

namespace App\Sources\GitHub;

use App\Models\Source;
use App\Normalizer;
use App\Sources\Branch;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use App\Sources\Traits\BearerToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GitHubClient extends Client
{
    use BearerToken;

    public function http(): PendingRequest
    {
        return Http::baseUrl($this->url)
            ->withHeader('Authorization', "Bearer $this->token");
    }

    /**
     * @throws ConnectionException
     */
    public function projects(?string $search = null): array
    {
        $response = $this->http()->get('/search/repositories', [
            'q' => $search,
        ]);

        $response->throw();

        $data = $response->json()['items'];

        return array_map(fn (array $item): Project => new Project(
            id: $item['id'],
            fullName: $item['full_name'],
            name: $item['name'],
            url: $item['url'],
            webUrl: $item['html_url'],
        ), $data);
    }

    /**
     * @throws ConnectionException
     */
    public function branches(Project $project): array
    {
        $response = $this->http()->get("$project->url/branches");

        $response->throw();

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
     * @throws ConnectionException
     */
    public function tags(Project $project): array
    {
        $response = $this->http()->get("$project->url/tags");

        $response->throw();

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
     * @throws ConnectionException
     */
    public function createWebhook(\App\Models\Repository $repository, Project $project, Source $source): void
    {
        $response = $this->http()->post("$project->url/hooks", [
            'config' => [
                'url' => url($repository->url("/incoming/github/$source->id")),
                'secret' => decrypt($source->secret),
                'content_type' => 'json',
            ],
            'events' => ['push', 'delete'],
            'active' => true,
        ]);

        $response->throw();
    }

    public function project(string $id): Project
    {
        $response = $this->http()->get("/repositories/$id");

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
