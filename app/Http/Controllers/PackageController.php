<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Packages\DestroyPackage;
use App\Actions\Packages\Inputs\StorePackageInput;
use App\Actions\Packages\StorePackage;
use App\Enums\Permission;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\SearchFilter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ) {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_READ);

        $packages = QueryBuilder::for(Package::userScoped())
            ->allowedFilters([
                SearchFilter::allowed(['name', 'description']),
                AllowedFilter::exact('repository_id'),
            ])
            ->allowedSorts([
                'downloads',
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

        $package = Package::userScoped()
            ->findOrFail($packageId);

        $package->load([
            'versions' => fn (HasMany $query) => $query->withCount('downloads'),
            'repository' => fn (BelongsTo $query) => $query->withCount('packages'),
            'source' => fn (BelongsTo $query) => $query,
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
}
