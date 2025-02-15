<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\FailedToOpenArchiveException;
use App\Exceptions\NameNotFoundException;
use App\Exceptions\VersionNotFoundException;
use App\Models\Package;
use App\Models\Source;
use App\Sources\Importable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;

class ImportImportable implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public function __construct(
        private readonly Source $source,
        private readonly Package $package,
        private readonly Importable $importable
    ) {
        //
    }

    /**
     * @throws FailedToFetchArchiveException
     * @throws ArchiveInvalidContentTypeException
     * @throws FailedToOpenArchiveException
     * @throws ComposerJsonNotFoundException
     * @throws NameNotFoundException
     * @throws VersionNotFoundException
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $this->source->client()->import(
            package: $this->package,
            importable: $this->importable,
        );
    }
}
