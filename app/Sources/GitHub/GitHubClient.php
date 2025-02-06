<?php

declare(strict_types=1);

namespace App\Sources\GitHub;

use App\Exceptions\InvalidTokenException;
use App\Models\Source;
use App\Normalizer;
use App\Sources\Branch;
use App\Sources\Client;
use App\Sources\Project;
use App\Sources\Tag;
use App\Sources\Traits\BearerToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
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
     * @throws ConnectionException|RequestException
     */
    public function projects(?string $search = null): array
    {
        $response = $this->http()->get('/search/repositories', [
            'q' => $search,
        ])->throw();

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
            'config' => [
                'url' => $repository->url("/incoming/github/$source->id"),
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
        $response = $this->http()->get("/repositories/$id")->throw();

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

    /**
     * @throws InvalidTokenException|ConnectionException
     */
    public function validateToken(): void
    {
        // Fine-grained personal access tokens, does not respond with scopes in header
        // lets try and manual check
        if (str_starts_with($this->token, 'github_pat_')) {
            $this->validateTokenManually();

            return;
        }

        $response = $this->http()
            ->get('/');

        $scopes = $response->header('X-OAuth-Scopes');
        $scopes = array_map(fn (string $value): string => trim($value), explode(',', $scopes));

        if (in_array('repo', $scopes)) {
            return;
        }

        throw new InvalidTokenException(
            missingScopes: ['repo']
        );
    }

    /**
     * @throws InvalidTokenException|ConnectionException
     */
    private function validateTokenManually(): void
    {
        try {
            $projects = $this->projects('is:private');
        } catch (\Exception) {
            throw new InvalidTokenException(missingScopes: ['contents read-only']);
        }

        if ($projects === []) {
            throw new InvalidTokenException(missingScopes: ['contents read-only']);
        }

        $project = $projects[0];

        $response = $this->http()->post("$project->url/hooks");

        if ($response->status() === 422) {
            return;
        }

        throw new InvalidTokenException(missingScopes: ['webhooks read and write']);
    }
}
