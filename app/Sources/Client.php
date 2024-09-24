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
use App\Models\Version;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use SensitiveParameter;

abstract class Client
{
    public function __construct(
        protected string $url,
        #[SensitiveParameter] protected string $token,
        private readonly Import $import,
    ) {
        //
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
     */
    abstract public function projects(): array;

    /**
     * @return Branch[]
     */
    abstract public function branches(Project $project): array;

    /**
     * @return Tag[]
     */
    abstract public function tags(Project $project): array;

    abstract public function createWebhook(Repository $repository, Project $project): void;
}
