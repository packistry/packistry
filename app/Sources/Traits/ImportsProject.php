<?php

declare(strict_types=1);

namespace App\Sources\Traits;

use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\FailedToOpenArchiveException;
use App\Exceptions\NameNotFoundException;
use App\Exceptions\VersionNotFoundException;
use App\Models\Package;
use App\Sources\Importable;
use App\Sources\Project;
use Illuminate\Http\Client\ConnectionException;

trait ImportsProject
{
    /**
     * @return string[][]
     *
     * @throws ArchiveInvalidContentTypeException
     * @throws ConnectionException
     * @throws FailedToFetchArchiveException
     * @throws FailedToOpenArchiveException
     * @throws NameNotFoundException
     * @throws VersionNotFoundException
     */
    private function importTags(Package $package, Project $project): array
    {
        return $this->importAll(
            $package,
            $this->client->tags($project)
        );
    }

    /**
     * @return string[][]
     *
     * @throws ArchiveInvalidContentTypeException
     * @throws ConnectionException
     * @throws FailedToFetchArchiveException
     * @throws FailedToOpenArchiveException
     * @throws NameNotFoundException
     * @throws VersionNotFoundException
     */
    private function importBranches(Package $package, Project $project): array
    {
        return $this->importAll(
            $package,
            $this->client->branches($project)
        );
    }

    private function createWebhook(Project $project): void
    {
        $this->client->createWebhook($this->repository, $project);
    }

    /**
     * @param  Importable[]  $imports
     * @return array<int, array{string}>
     *
     * @throws VersionNotFoundException
     * @throws FailedToFetchArchiveException
     * @throws ConnectionException
     * @throws ArchiveInvalidContentTypeException
     * @throws FailedToOpenArchiveException
     * @throws NameNotFoundException
     */
    private function importAll(Package $package, array $imports): array
    {
        return array_map(function (Importable $tag) use ($package): array {
            try {
                $version = $this->client->import(
                    package: $package,
                    importable: $tag,
                );
            } catch (ComposerJsonNotFoundException) {
                return ["{$tag->version()}: failed, composer.json is missing"];
            }

            return [$version->name];
        }, $imports);
    }
}
