<?php

declare(strict_types=1);

namespace App\Sources;

use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\FailedToOpenArchiveException;
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
use SensitiveParameter;

abstract class Client
{
    protected string $url;

    protected string $token;

    public function __construct(
        private readonly Import $import,
    ) {
        //
    }

    public function withOptions(
        #[SensitiveParameter] string $token,
        string $url
    ): static {
        $this->token = $token;
        $this->url = $url;

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
     * @return Branch[]
     */
    abstract public function branches(Project $project): array;

    /**
     * @return Tag[]
     */
    abstract public function tags(Project $project): array;

    /**
     * @throws RequestException
     */
    abstract public function createWebhook(Repository $repository, Project $project, Source $source): void;

    abstract public function validateToken(): void;
}
