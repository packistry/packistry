<?php

declare(strict_types=1);

namespace App;

use App\Enums\PackageType;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToOpenArchiveException;
use App\Exceptions\VersionNotFoundException;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use App\Traits\ComposerFromZip;
use App\Traits\NormalizesVersion;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CreateFromZip
{
    use ComposerFromZip;
    use NormalizesVersion;

    /**
     * @throws VersionNotFoundException
     * @throws ComposerJsonNotFoundException
     * @throws FailedToOpenArchiveException
     */
    public function create(
        Repository $repository,
        string $path,
        string $name,
        ?string $version = null,
    ): Version {
        $decoded = $this->decodedComposerJsonFromZip($path);

        $version ??= $decoded['version'] ?? throw new VersionNotFoundException('no version provided');

        /** @var Package $package */
        $package = $repository
            ->packages()
            ->where('name', $name)
            ->first() ?? new Package;

        if (! $package->exists) {
            $package->name = $name;
            $package->description = $decoded['description'] ?? null;
            $package->type = array_key_exists('type', $decoded)
                ? PackageType::tryFrom($decoded['type']) ?? PackageType::LIBRARY
                : PackageType::LIBRARY;

            $repository->packages()->save($package);
            $package->save();
        }

        $createdVersion = $package
            ->versions()
            ->where('name', $this->normalizeVersion($version))
            ->first() ?? new Version;

        $hash = hash_file('sha1', $path);

        if ($hash === false) {
            throw new RuntimeException('failed to calculate hash');
        }

        $createdVersion->package_id = $package->id;
        $createdVersion->name = $version;
        $createdVersion->shasum = $hash;
        $createdVersion->metadata = collect($decoded)->only([
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

        $createdVersion->save();

        /** @var string $contents */
        $contents = file_get_contents($path);

        Storage::disk()->put(
            path: $repository->archivePath(str_replace('/', '-', $name)."-$version.zip"),
            contents: $contents
        );

        return $createdVersion;
    }
}
