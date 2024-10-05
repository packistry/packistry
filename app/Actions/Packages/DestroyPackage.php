<?php

declare(strict_types=1);

namespace App\Actions\Packages;

use App\Archive;
use App\Models\Package;
use App\Models\Version;
use Illuminate\Support\Facades\Storage;

class DestroyPackage
{
    public function handle(Package $package): Package
    {
        $paths = $package->versions()
            ->get()
            ->map(function (Version $version) use ($package): string {
                return Archive::name($package, $version->name);
            });

        foreach ($paths as $path) {
            dispatch(function () use ($path) {
                Storage::disk()->delete($path);
            });
        }

        $package->delete();

        return $package;
    }
}
