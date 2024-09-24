<?php

declare(strict_types=1);

namespace App\Sources\Gitlab;

use App\Sources\Branch;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GitlabClient extends Client
{
    public function http(): PendingRequest
    {
        return Http::withHeader('Private-Token', $this->token);
    }

    /**
     * @throws ConnectionException
     */
    public function projects(): array
    {
        $response = $this->http()->get('/api/v4/projects');

        /** @var array<string, mixed> $data */
        $data = $response->json();

        return array_map(fn (array $item): Project => new Project(
            id: $item['id'],
            fullName: $item['path_with_namespace'],
            name: $item['name'],
            url: $item['_links']['self'].'/repository',
            webUrl: $item['web_url'],
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

        return array_map(function (array $item) use ($project): Branch {
            $sha = $item['commit']['id'];

            return new Branch(
                name: $item['name'],
                projectFullName: $project->fullName,
                zipUrl: "$project->url/archive.zip?sha=$sha",
                subDirectory: "$project->name-$sha-$sha/",
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
                name: $item['name'],
                projectFullName: $project->fullName,
                zipUrl: "$project->url/archive.zip?sha=$sha",
                subDirectory: "$project->name-$sha-$sha/",
            );
        }, $data);
    }

    /**
     * @throws ConnectionException
     */
    public function createWebhook(Project $project): void
    {
        $this->http()->post("$project->url/hooks", [
            'url' => url('incoming/gitlab'),
            'name' => 'conductor sync',
            'token' => config('services.gitea.webhook.secret'),
            'content_type' => 'json',
            'tag_push_events' => true,
            'branch_push_events' => true,
        ]);
    }
}
