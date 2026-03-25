<?php

declare(strict_types=1);

namespace App\Actions\Packages;

use App\Actions\Packages\Inputs\UploadPackageZipInput;
use App\CreateFromZip;
use App\Enums\PackageType;
use App\Exceptions\NameNotFoundException;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use App\Traits\ComposerFromZip;

class UploadPackageZip
{
    use ComposerFromZip;

    public function __construct(private readonly CreateFromZip $createFromZip) {}

    public function handle(UploadPackageZipInput $input): Version
    {
        /** @var Repository $repository */
        $repository = Repository::query()->findOrFail($input->repository);

        $decoded = $this->decodedComposerJsonFromZip($input->filePath);
        $packageName = $decoded['name'] ?? throw new NameNotFoundException('no name provided');

        /** @var Package|null $package */
        $package = $repository
            ->packages()
            ->where('name', $packageName)
            ->first();

        if ($package === null) {
            $package = new Package;
            $package->repository_id = $repository->id;
            $package->type = PackageType::LIBRARY->value;
            $package->name = $packageName;
            $package->save();
        }

        return $this->createFromZip->create(
            package: $package,
            path: $input->filePath,
            version: $input->version,
        );
    }
}
