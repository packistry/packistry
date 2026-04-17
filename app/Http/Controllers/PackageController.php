<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Packages\DestroyPackage;
use App\Actions\Packages\Inputs\StorePackageInput;
use App\Actions\Packages\Inputs\UploadPackageZipInput;
use App\Actions\Packages\ImportMissingPackage;
use App\Actions\Packages\RebuildPackage;
use App\Actions\Packages\StorePackage;
use App\Actions\Packages\UploadPackageZip;
use App\Enums\Permission;
use App\Http\Resources\PackageResource;
use App\Http\Resources\VersionResource;
use App\Models\Builders\RepositoryBuilder;
use App\Models\Download;
use App\Models\Package;
use App\Models\Repository;
use App\SearchFilter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

readonly class PackageController extends Controller
{
    public function __construct(
        private StorePackage $storePackage,
        private DestroyPackage $destroyPackage,
        private RebuildPackage $rebuildPackage,
        private ImportMissingPackage $importMissingPackage,
        private UploadPackageZip $uploadPackageZip,
    ) {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_READ);

        $packages = QueryBuilder::for(Package::query()->userScoped())
            ->allowedFilters([
                SearchFilter::allowed(['name', 'description']),
                AllowedFilter::exact('repository_id'),
            ])
            ->allowedIncludes([
                'repository',
            ])
            ->allowedSorts([
                'total_downloads',
                'name',
            ])
            ->paginate((int) $request->query('size', '10'));

        return PackageResource::collection($packages)
            ->toResponse($request);
    }

    /**
     * @throws Throwable
     */
    public function store(StorePackageInput $input): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_CREATE);

        $packages = $this->storePackage->handle($input);

        return response()->json(
            PackageResource::collection($packages),
            201,
        );
    }

    public function show(string $packageId): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_READ);

        $package = Package::query()
            ->userScoped()
            ->findOrFail($packageId);

        $package->load([
            'repository' => function (BelongsTo $query): void {
                $repositoryQuery = $query->getQuery();
                if ($repositoryQuery instanceof RepositoryBuilder) {
                    $repositoryQuery->withUserScopedPackageCount();
                }
            },
            'source',
        ]);

        return response()->json(
            new PackageResource($package)
        );
    }

    public function destroy(string $packageId): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_DELETE);

        $package = $this->destroyPackage->handle(
            package: Package::userScoped()->findOrFail($packageId),
        );

        return response()->json(
            new PackageResource($package)
        );
    }

    public function downloads(string $packageId): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_READ);

        $package = Package::userScoped()
            ->findOrFail($packageId);

        $dates = Download::perDayForPackages(90, $package->id);

        return response()->json($dates);
    }

    /**
     * @throws Throwable
     */
    public function rebuild(string $packageId): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_UPDATE);

        $package = Package::userScoped()
            ->findOrFail($packageId);

        $this->rebuildPackage->handle($package);

        return response()->json(
            new PackageResource($package)
        );
    }

    /**
     * @throws Throwable
     */
    public function importMissing(string $packageId): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_UPDATE);

        $package = Package::userScoped()
            ->findOrFail($packageId);

        $this->importMissingPackage->handle($package);

        return response()->json(
            new PackageResource($package)
        );
    }

    /**
     * @throws Throwable
     */
    public function upload(Request $request, string $repositoryId): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_CREATE);

        $request->validate([
            'file' => ['required', 'file', 'mimes:zip'],
            'version' => ['nullable', 'string'],
        ]);

        /** @var Repository $repository */
        $repository = Repository::query()->findOrFail($repositoryId);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $version = $this->uploadPackageZip->handle(new UploadPackageZipInput(
            repository: (string) $repository->id,
            filePath: (string) $file->getRealPath(),
            version: $request->string('version')->toString() !== '' ? $request->string('version')->toString() : null,
        ));

        return response()->json(
            new VersionResource($version),
            201,
        );
    }
}
