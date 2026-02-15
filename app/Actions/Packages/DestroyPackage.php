<?php

declare(strict_types=1);

namespace App\Actions\Packages;

use App\Models\Package;
use App\Models\Version;
use Illuminate\Support\Facades\Storage;

class DestroyPackage
{
    public function handle(Package $package): Package
    {
        $paths = $package->versions()
            ->get()
            ->map(fn (Version $version) => $version->archive_path)
            ->filter(fn (?string $path) => $path !== null)
            ->toArray();

        dispatch(function () use ($paths): void {
            Storage::disk()->delete($paths);
        });

        $package->delete();

        return $package;
    }
}
