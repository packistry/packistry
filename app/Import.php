<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\FailedToOpenArchiveException;
use App\Exceptions\VersionNotFoundException;
use App\Models\Repository;
use App\Models\Version;
use App\Sources\Importable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;

readonly class Import
{
    public function __construct(private CreateFromZip $createFromZip)
    {
        //
    }

    /**
     * @throws ArchiveInvalidContentTypeException
     * @throws ConnectionException
     * @throws ComposerJsonNotFoundException
     * @throws VersionNotFoundException
     * @throws FailedToFetchArchiveException
     * @throws FailedToOpenArchiveException
     */
    public function import(Repository $repository, Importable $importable, PendingRequest $http): Version
    {
        [$temp, $path] = $this->downloadZip($importable, $http);

        try {
            return $this->createFromZip->create(
                repository: $repository,
                path: $path,
                name: $importable->name(),
                version: $importable->version(),
            );
        } finally {
            fclose($temp);
        }
    }

    /**
     * @return array{resource, string}
     *
     * @throws ArchiveInvalidContentTypeException
     * @throws ConnectionException
     * @throws FailedToFetchArchiveException
     */
    private function downloadZip(Importable $importable, PendingRequest $http): array
    {
        $temp = tmpfile();
        $path = stream_get_meta_data($temp)['uri'];

        $response = $http->get($importable->zipUrl());

        if ($response->failed()) {
            return throw new FailedToFetchArchiveException($response->body());
        }

        $contentType = $response->header('Content-Type');

        if (! Str::contains($contentType, ['application/zip', 'application/octet-stream'])) {
            return throw new ArchiveInvalidContentTypeException("Invalid content-type: $contentType");
        }

        file_put_contents($path, $response->body());

        return [$temp, $path];
    }
}
