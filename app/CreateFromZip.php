<?php

declare(strict_types=1);

namespace App;

use App\Enums\PackageType;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToOpenArchiveException;
use App\Exceptions\NameNotFoundException;
use App\Exceptions\VersionNotFoundException;
use App\Models\Package;
use App\Models\Version;
use App\Traits\ComposerFromZip;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CreateFromZip
{
    use ComposerFromZip;

    /**
     * @throws VersionNotFoundException
     * @throws ComposerJsonNotFoundException
     * @throws FailedToOpenArchiveException
     * @throws NameNotFoundException
     */
    public function create(
        Package $package,
        string $path,
        ?string $version = null,
    ): Version {
        $decoded = $this->decodedComposerJsonFromZip($path);

        $version ??= $decoded['version'] ?? throw new VersionNotFoundException('no version provided');
        $name = $decoded['name'] ?? throw new NameNotFoundException('no name provided');

        $package->name = $name;

        $package->description = $decoded['description'] ?? null;
        $package->type = array_key_exists('type', $decoded) && $decoded['type'] !== '' && $decoded['type'] !== null
            ? $decoded['type']
            : PackageType::LIBRARY->value;

        if ($package->isDirty()) {
            $package->save();
        }

        $createdVersion = $package
            ->versions()
            ->where('name', Normalizer::version($version))
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
            path: Archive::name($package, $version),
            contents: $contents
        );

        return $createdVersion;
    }
}
