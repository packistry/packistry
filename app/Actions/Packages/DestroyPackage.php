<?php

declare(strict_types=1);

namespace App\Actions\Packages;

use App\Models\Package;

class DestroyPackage
{
    public function handle(Package $package): Package
    {
        // @todo clean up archives
        $package->delete();

        return $package;
    }
}
