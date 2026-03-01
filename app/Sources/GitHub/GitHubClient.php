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
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;

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
        $query = $search;

        if ($search !== null && str_contains($search, '/')) {
            [$org, $name] = explode('/', $search, 2);
            $query = trim($name) !== ''
                ? "{$name} org:{$org}"
                : "org:{$org}";
        }

        $response = $this->http()->get('/search/repositories', [
            'q' => $query,
            'per_page' => 100,
        ]);

        if ($response->status() === 422) {
            return [];
        }

        $response->throw();

        $data = $response->json()['items'];

        return array_map(fn (array $item): Project => new Project(
            id: $item['id'],
            fullName: $item['full_name'],
            name: $item['name'],
            url: $item['url'],
            webUrl: $item['html_url'],
            readOnly: $item['archived'] || $item['disabled'],
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
                zipUrl: "$project->url/zipball/refs/heads/{$item['name']}",
                sourceUrl: $project->webUrl,
                reference: $item['commit']['sha'] ?? null,
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
                reference: $item['commit']['sha'] ?? null,
            ));
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
            readOnly: ((bool) $item['archived']) || ((bool) $item['disabled']),
        );
    }

    /**
     * @throws InvalidTokenException|ConnectionException
     */
    public function validateToken(): void
    {
        // Fine-grained personal access tokens do not respond with scopes in the header
        // let's try and manual check
        if (str_starts_with($this->token, 'github_pat_')) {
            $this->validateTokenManually();

            return;
        }

        $response = $this->http()
            ->get('/');

        $scopes = $response->header('X-OAuth-Scopes');
        $scopes = array_map(fn (string $value): string => trim($value), explode(',', $scopes));

        if (in_array('repo', $scopes, true)) {
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
        } catch (Exception) {
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
            $nextUri = $uri;

            while ($nextUri) {
                $response = $this->http()->get($nextUri)
                    ->throw();

                $data = $response->json();

                foreach ($data as $item) {
                    yield $item;
                }

                $link = $response->header('link');

                if (blank($link)) {
                    break;
                }

                $matches = [];
                preg_match('/<([^>]+?)>; rel="next"/', $link, $matches);

                $nextUri = $matches[1] ?? null;
            }
        });
    }
}
