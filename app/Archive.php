<?php

declare(strict_types=1);

namespace App;

use App\Models\Package;

class Archive
{
    public static function name(Package $package, string $version): string
    {
        $name = str_replace('/', '-', $package->name);
        $archiveName = "$name-$version.zip";

        return $package->repository->archivePath($archiveName);
    }
}
