<?php

declare(strict_types=1);

namespace App\Sources\Gitea;

use App\Normalizer;
use App\Sources\Branch;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use App\Sources\Traits\BearerToken;
use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

class GiteaClient extends Client
{
    use BearerToken;

    /**
     * @throws ConnectionException
     */
    public function projects(?string $search = null): array
    {
        $response = $this->http()->get('/api/v1/repos/search', [
            'q' => $search,
        ]);

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
     * @throws ConnectionException
     */
    public function branches(Project $project): array
    {
        $response = $this->http()->get("$project->url/branches");

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
    public function createWebhook(\App\Models\Repository $repository, Project $project): void
    {
        $this->http()->post("$project->url/hooks", [
            'type' => 'gitea',
            'config' => [
                'url' => url($repository->url('/incoming/gitea')),
                'secret' => config('services.gitea.webhook.secret'),
                'content_type' => 'json',
            ],
            'events' => ['push', 'delete'],
            'active' => true,
        ]);
    }

    public function project(string $id): Project
    {
        throw new RuntimeException('Not implemented yet');
        //        $response = $this->http()->get('/api/v1/repos/search');
        //
        //        /** @var array<string, mixed> $data */
        //        $data = $response->json()['data'];
        //
        //        return new Project(
        //            id: $item['id'],
        //            fullName: $item['full_name'],
        //            name: $item['name'],
        //            url: $item['url'],
        //            webUrl: $item['html_url'],
        //        );
    }
}
