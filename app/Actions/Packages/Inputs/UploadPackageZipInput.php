<?php

declare(strict_types=1);

namespace App\Actions\Packages\Inputs;

use App\Actions\Input;

class UploadPackageZipInput extends Input
{
    public function __construct(
        public string $repository,
        public string $filePath,
        public ?string $version = null,
    ) {
        //
    }
}
