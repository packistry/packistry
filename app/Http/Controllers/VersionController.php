<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\VersionResource;
use App\Models\Package;
use App\SearchFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

readonly class VersionController extends Controller
{
    public function index(Request $request, string $packageId): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_READ);

        $package = Package::query()->userScoped()->findOrFail($packageId);

        $packages = QueryBuilder::for(
            $package->versions()
        )
            ->allowedFilters([
                SearchFilter::allowed(['name']),
            ])
            ->allowedSorts([
                'total_downloads',
                'name',
                'created_at',
            ])
            ->paginate((int) $request->query('size', '10'));

        return VersionResource::collection($packages)
            ->toResponse($request);
    }
}
