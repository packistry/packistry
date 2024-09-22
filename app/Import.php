<?php

declare(strict_types=1);

namespace App;

use App\Incoming\Importable;
use App\Models\Repository;
use App\Models\Version;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;
use Throwable;

readonly class Import
{
    public function __construct(private CreateFromZip $createFromZip)
    {
        //
    }

    /**
     * @throws Exceptions\ComposerJsonNotFoundException
     * @throws Exceptions\VersionNotFoundException
     * @throws Throwable
     */
    public function import(Repository $repository, Importable $importable, PendingRequest $client): Version
    {
        $temp = tmpfile();
        $path = stream_get_meta_data($temp)['uri'];

        $response = $client->get($importable->zipUrl());

        if ($response->failed()) {
            return throw new RuntimeException('failed to fetch zip');
        }

        file_put_contents($path, $response->body());

        return $this->createFromZip->create(
            repository: $repository,
            path: $path,
            name: $importable->name(),
            subDirectory: $importable->subDirectory(),
            version: $importable->version(),
        );
    }
}
