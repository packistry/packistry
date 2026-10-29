<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\FailedToOpenArchiveException;
use App\Models\Package;
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

    public function import(Package $package, Importable $importable, PendingRequest $http): Version
    {
        [$temp, $path] = $this->downloadZip($importable, $http);

        try {
            $version = $this->createFromZip->create(
                package: $package,
                path: $path,
                version: $importable->version(),
                imported: true,
            );

            $metadata = $version->metadata ?? [];
            $metadata['source'] = [
                'type' => 'git',
                'url' => $importable->sourceUrl(),
                'reference' => $importable->reference(),
            ];

            $version->metadata = $metadata;
            $version->save();

            return $version;
        } finally {
            fclose($temp);
        }
    }

    /**
     * @return array{resource, string}
     *
     * @throws ConnectionException|FailedToFetchArchiveException|FailedToOpenArchiveException|ArchiveInvalidContentTypeException
     */
    private function downloadZip(Importable $importable, PendingRequest $http): array
    {
        $temp = tmpfile();
        if ($temp === false) {
            throw new FailedToOpenArchiveException('Failed to create temporary file.');
        }

        $meta = stream_get_meta_data($temp);
        if (! isset($meta['uri'])) {
            throw new FailedToOpenArchiveException('Temporary file path is unavailable.');
        }

        $path = $meta['uri'];

        // sink the archive straight to the temporary file, so it is never held in memory
        $response = $http->send('GET', $importable->zipUrl(), [
            'sink' => $path,
        ]);

        if ($response->failed()) {
            return throw new FailedToFetchArchiveException($response->body());
        }

        $contentType = $response->header('Content-Type');

        if (! Str::contains($contentType, ['application/zip', 'application/octet-stream'])) {
            return throw new ArchiveInvalidContentTypeException("Invalid content-type: $contentType");
        }

        return [$temp, $path];
    }
}
