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
        ), $data);
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function branches(Project $project): array
    {
        $branches = [];
        $perPage = 100;
        $page = 1;

        do {
            $response = $this->http()->get("$project->url/repository/branches", [
                'per_page' => $perPage,
                'page' => $page,
            ])->throw();

            $data = $response->json();

            if (is_null($data)) {
                throw new RuntimeException($response->getBody()->getContents());
            }

            foreach ($data as $branch) {
                $sha = $branch['commit']['id'];

                $branches[] = new Branch(
                    id: (string) $project->id,
                    name: $branch['name'],
                    url: $project->url,
                    zipUrl: "$project->url/repository/archive.zip?sha=$sha",
                );
            }

            $page++;
        } while ($response->header('x-next-page'));

        return $branches;
    }

    /**
     * @throws ConnectionException|RequestException
     */
    public function tags(Project $project): array
    {
        $tags = [];
        $perPage = 100;
        $page = 1;

        do {
            $response = $this->http()->get("$project->url/repository/tags", [
                'per_page' => $perPage,
                'page' => $page,
            ])->throw();

            $data = $response->json();

            if (is_null($data)) {
                throw new RuntimeException($response->getBody()->getContents());
            }

            foreach ($data as $tag) {
                $sha = $tag['commit']['id'];

                $tags[] = new Tag(
                    id: (string) $project->id,
                    name: $tag['name'],
                    url: $project->url,
                    zipUrl: "$project->url/repository/archive.zip?sha=$sha",
                );
            }

            $page++;
        } while ($response->header('x-next-page'));

        return $tags;
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
