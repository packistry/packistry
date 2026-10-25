<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\VersionResource;
use App\Mirror\MetadataCache;
use App\Models\Download;
use App\Models\Package;
use App\Models\Upstream;
use App\SearchFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder;

readonly class VersionController extends Controller
{
    public function __construct(private MetadataCache $metadata)
    {
        //
    }

    public function index(Request $request, string $packageId): JsonResponse
    {
        $this->authorize(Permission::PACKAGE_READ);

        /** @var Package $package */
        $package = Package::query()->userScoped()->findOrFail($packageId);

        if ($package->isMirrored()) {
            return $this->mirrored($request, $package);
        }

        $packages = QueryBuilder::for(
            $package->versions()
        )
            ->allowedFilters(
                SearchFilter::allowed(['name']),
            )
            ->allowedSorts(
                'total_downloads',
                'name',
                'created_at',
            )
            ->paginate((int) $request->query('size', '10'));

        return VersionResource::collection($packages)
            ->toResponse($request);
    }

    /**
     * Versions of a mirrored package come from the cached upstream metadata.
     */
    private function mirrored(Request $request, Package $package): JsonResponse
    {
        /** @var Upstream|null $upstream */
        $upstream = Upstream::query()->with('credentials')->find($package->upstream_id);

        $downloads = Download::query()
            ->where('package_id', $package->id)
            ->selectRaw('version_name, COUNT(*) as count')
            ->groupBy('version_name')
            ->pluck('count', 'version_name');

        /** @var Collection<int, array<string, mixed>> $versions */
        $versions = collect($upstream === null ? [] : [false, true])
            ->flatMap(function (bool $dev) use ($upstream, $package): array {
                /** @var Upstream $upstream */
                $result = $this->metadata->get($upstream, $package->name, $dev);

                return $result->isFound() ? $result->versions($package->name) : [];
            })
            ->map(fn (array $version): array => [
                // mirrored versions have no database row, the name identifies them
                'id' => $name = (string) ($version['version'] ?? ''),
                'package_id' => $package->id,
                'name' => $name,
                'shasum' => is_array($version['dist'] ?? null) ? ($version['dist']['shasum'] ?? null) : null,
                'total_downloads' => (int) $downloads->get($name, 0),
                'created_at' => $version['time'] ?? null,
                'updated_at' => $version['time'] ?? null,
            ])
            ->unique('name')
            ->values();

        $search = $request->input('filter.search');

        if (is_string($search) && $search !== '') {
            $versions = $versions->filter(
                fn (array $version): bool => Str::contains((string) $version['name'], $search, ignoreCase: true)
            );
        }

        $sort = $request->query('sort');

        if (is_string($sort) && in_array(ltrim($sort, '-'), ['total_downloads', 'name', 'created_at'], true)) {
            $versions = $versions->sortBy(ltrim($sort, '-'), descending: str_starts_with($sort, '-'));
        }

        $size = (int) $request->query('size', '10');
        $page = LengthAwarePaginator::resolveCurrentPage();

        $paginator = new LengthAwarePaginator(
            $versions->forPage($page, $size)->values(),
            $versions->count(),
            $size,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return JsonResource::collection($paginator)
            ->toResponse($request);
    }
}
