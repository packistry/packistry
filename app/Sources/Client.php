<?php

declare(strict_types=1);

namespace App\Sources;

use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\FailedToOpenArchiveException;
use App\Exceptions\InvalidTokenException;
use App\Exceptions\NameNotFoundException;
use App\Exceptions\VersionNotFoundException;
use App\Import;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Source;
use App\Models\Version;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\LazyCollection;
use SensitiveParameter;

abstract class Client
{
    protected string $url;

    protected string $token;

    /**
     * @var array<string, mixed>
     */
    protected ?array $metadata = [];

    public function __construct(
        private readonly Import $import,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function withOptions(
        #[SensitiveParameter] string $token,
        string $url,
        ?array $metadata = null,
    ): static {
        $this->token = $token;
        $this->url = $url;
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @throws VersionNotFoundException
     * @throws FailedToFetchArchiveException
     * @throws ConnectionException
     * @throws ArchiveInvalidContentTypeException
     * @throws ComposerJsonNotFoundException
     * @throws FailedToOpenArchiveException
     * @throws NameNotFoundException
     */
    public function import(Package $package, Importable $importable): Version
    {
        return $this->import->import(
            package: $package,
            importable: $importable,
            http: $this->http(),
        );
    }

    abstract public function http(): PendingRequest;

    /**
     * @return Project[]
     *
     * @throws ConnectionException|RequestException
     */
    abstract public function projects(?string $search = null): array;

    abstract public function project(string $id): Project;

    /**
     * @return LazyCollection<int, Branch>
     */
    abstract public function branches(Project $project): LazyCollection;

    /**
     * @return LazyCollection<int, Tag>
     */
    abstract public function tags(Project $project): LazyCollection;

    /**
     * @throws RequestException
     */
    abstract public function createWebhook(Repository $repository, Project $project, Source $source): void;

    /**
     * @throws InvalidTokenException
     */
    abstract public function validateToken(): void;
}
