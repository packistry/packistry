<?php

declare(strict_types=1);

namespace App;

use App\Enums\PackageType;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToOpenArchiveException;
use App\Exceptions\InvalidConstraintException;
use App\Exceptions\NameNotFoundException;
use App\Exceptions\PackageNameMismatchException;
use App\Exceptions\VersionNotFoundException;
use App\Models\Package;
use App\Models\Version;
use App\Traits\ComposerFromZip;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CreateFromZip
{
    use ComposerFromZip;

    public function __construct(private readonly ConstraintValidator $constraintValidator)
    {
        //
    }

    /**
     * @throws VersionNotFoundException
     * @throws ComposerJsonNotFoundException
     * @throws FailedToOpenArchiveException
     * @throws NameNotFoundException
     * @throws InvalidConstraintException
     * @throws PackageNameMismatchException
     */
    public function create(
        Package $package,
        string $path,
        ?string $version = null,
        bool $imported = false,
    ): Version {
        $decoded = $this->decodedComposerJsonFromZip($path);

        $invalidConstraints = $this->constraintValidator->invalidConstraints($decoded);

        if ($invalidConstraints !== []) {
            throw new InvalidConstraintException($invalidConstraints);
        }

        // a local version takes over a package that was mirrored from an upstream
        $package->convertToLocal();

        $version ??= $decoded['version'] ?? throw new VersionNotFoundException('no version provided');
        $name = $decoded['name'] ?? throw new NameNotFoundException('no name provided');

        $currentOrder = Normalizer::versionOrder($version);

        $this->applyName($package, $name, $currentOrder, $imported);

        $package->description = $decoded['description'] ?? null;
        $package->type = array_key_exists('type', $decoded) && $decoded['type'] !== '' && $decoded['type'] !== null
            ? $decoded['type']
            : PackageType::LIBRARY->value;

        if ($package->isDirty()) {
            $package->save();
        }

        $createdVersion = $package
            ->versions()
            ->where('name', $versionName = Normalizer::version($version))
            ->first() ?? new Version;

        $hash = hash_file('sha1', $path);

        if ($hash === false) {
            throw new RuntimeException('failed to calculate hash');
        }

        $createdVersion->package_id = $package->id;
        $createdVersion->name = $versionName;
        $createdVersion->order = $currentOrder;
        $createdVersion->shasum = $hash;
        $createdVersion->archive_path = $package->repository->archivePath(Str::uuid7()->toString().'.zip');
        $createdVersion->metadata = collect($decoded)->only([
            'description',
            'readme',
            'keywords',
            'homepage',
            'license',

            'authors',
            'support',
            'funding',

            'bin',

            'autoload',
            'autoload-dev',

            'require',
            'require-dev',
            'conflict',
            'provide',
            'replace',
            'suggest',

            'minimum-stability',
            'prefer-stable',

            'scripts',
            'extra',
            'config',
            'repositories',

            'archive',
            'abandoned',

            '_comment',
            'non-feature-branches',
        ])->toArray();

        $createdVersion->save();

        /** @var string $contents */
        $contents = file_get_contents($path);

        Storage::disk()->put(
            path: $createdVersion->archive_path,
            contents: $contents
        );

        return $createdVersion;
    }

    /**
     * Imports start with their git project path as name, so their first version names the package.
     *
     * @throws PackageNameMismatchException
     */
    private function applyName(Package $package, string $name, string $order, bool $imported): void
    {
        if (Str::lower($name) === Str::lower($package->name)) {
            return;
        }

        if (! $imported) {
            throw new PackageNameMismatchException($name, $package->name);
        }

        if (! $package->versions()->exists()) {
            $package->name = $name;

            return;
        }

        if (! $this->isNewest($package, $order)) {
            return;
        }

        if (! $this->hasSameVendor($name, $package->name)) {
            throw new PackageNameMismatchException($name, $package->name);
        }

        $package->name = $name;
    }

    private function isNewest(Package $package, string $order): bool
    {
        return $order >= $package->versions()->max('order');
    }

    private function hasSameVendor(string $name, string $otherName): bool
    {
        return Str::lower(Str::before($name, '/')) === Str::lower(Str::before($otherName, '/'));
    }
}
