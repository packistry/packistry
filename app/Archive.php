<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\VersionNotFoundException;
use App\Models\Package;

class Archive
{
    /**
     * @throws VersionNotFoundException
     */
    public static function name(Package $package, string $version): string
    {
        $name = str_replace('/', '-', $package->name);

        $version = Normalizer::version($version);
        $archiveName = "$name-$version.zip";

        return $package->repository->archivePath($archiveName);
    }
}
