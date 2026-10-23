<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Packages\DestroyPackage;
use App\Actions\Packages\Inputs\StorePackageInput;
use App\Actions\Packages\RebuildPackage;
use App\Actions\Packages\RefreshMirroredPackage;
use App\Actions\Packages\StorePackage;
use App\Enums\Permission;
use App\Http\Resources\PackageResource;
use App\Models\Download;
use App\Models\Package;
use App\SearchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

readonly class PackageController extends Controller
{
    public function __construct(
        private StorePackage $storePackage,
        private DestroyPackage $destroyPackage,
        private RebuildPackage $rebuildPackage,
        private RefreshMirroredPackage $refreshMirroredPackage,
    ) {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_READ);

        $packages = QueryBuilder::for(Package::query()->userScoped())
            ->allowedFilters(
                SearchFilter::allowed(['name', 'description']),
                AllowedFilter::exact('repository_id'),
                AllowedFilter::exact('upstream_id'),
                AllowedFilter::callback('origin', fn (Builder $query, mixed $value) => match ($value) {
                    'mirrored' => $query->whereNotNull('mirror_status'),
                    'all' => $query,
                    default => $query->whereNull('mirror_status'),
                })->default('local'),
            )
            ->allowedIncludes(
                'repository',
                'upstream',
            )
            ->allowedSorts(
                'total_downloads',
                'name',
            )
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
                $query->getQuery()->withUserScopedPackageCount();
            },
            'source',
            'upstream',
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
     * Fetch the metadata of a mirrored package from its upstream now.
     */
    public function refresh(string $packageId): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_UPDATE);

        $package = $this->refreshMirroredPackage->handle(
            Package::userScoped()->findOrFail($packageId)
        );

        return response()->json(
            new PackageResource($package->load('upstream'))
        );
    }
}
