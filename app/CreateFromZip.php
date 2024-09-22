<?php

declare(strict_types=1);

namespace App;

use App\Enums\PackageType;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\VersionNotFoundException;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CreateFromZip
{
    /**
     * @throws VersionNotFoundException|ComposerJsonNotFoundException
     */
    public function create(
        Repository $repository,
        string $path,
        string $name,
        ?string $subDirectory = null,
        ?string $version = null,
    ): Version {
        $content = @file_get_contents("zip://$path#{$subDirectory}composer.json");

        if ($content === false) {
            return throw new ComposerJsonNotFoundException('composer.json not found in archive');
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $version ??= $decoded['version'] ?? throw new VersionNotFoundException('no version provided');

        /** @var Package $package */
        $package = $repository
            ->packages()
            ->where('name', $name)
            ->first() ?? new Package;

        if (! $package->exists) {
            $package->name = $name;
            $package->type = array_key_exists('type', $decoded)
                ? PackageType::tryFrom($decoded['type']) ?? PackageType::LIBRARY
                : PackageType::LIBRARY;

            $repository->packages()->save($package);
            $package->save();
        }

        $archiveName = str_replace('/', '-', $name)."-$version.zip";

        $newVersion = $package
            ->versions()
            ->where('name', $version)
            ->first() ?? new Version;

        $hash = hash_file('sha1', $path);

        if ($hash === false) {
            throw new RuntimeException('failed to calculate hash');
        }

        $newVersion->package_id = $package->id;
        $newVersion->name = $version;
        $newVersion->shasum = $hash;
        $newVersion->metadata = collect($decoded)->only([
            'description',
            'readme',
            'keywords',
            'homepage',
            'license',
            'authors',
            'bin',
            'autoload',
            'autoload-dev',
            'extra',
            'require',
            'require-dev',
            'suggest',
            'provide',
        ])->toArray();

        $newVersion->save();

        /** @var string $content */
        $content = file_get_contents($path);
        Storage::disk()->put($archiveName, $content);

        return $newVersion;
    }
}
