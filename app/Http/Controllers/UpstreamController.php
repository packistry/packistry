<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Upstreams\DestroyUpstream;
use App\Actions\Upstreams\Inputs\StoreUpstreamInput;
use App\Actions\Upstreams\Inputs\UpdateUpstreamInput;
use App\Actions\Upstreams\StoreUpstream;
use App\Actions\Upstreams\UpdateUpstream;
use App\Enums\MirrorStatus;
use App\Enums\Permission;
use App\Exceptions\UnsupportedUpstreamException;
use App\Exceptions\UpstreamUnavailableException;
use App\Http\Resources\UpstreamResource;
use App\Mirror\UpstreamClient;
use App\Models\Upstream;
use App\SearchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

readonly class UpstreamController extends Controller
{
    public function __construct(
        private StoreUpstream $storeUpstream,
        private UpdateUpstream $updateUpstream,
        private DestroyUpstream $destroyUpstream,
        private UpstreamClient $client,
    ) {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize(Permission::UPSTREAM_READ);

        $upstreams = QueryBuilder::for($this->query())
            ->allowedFilters(
                SearchFilter::allowed(['name', 'url']),
            )
            ->allowedSorts('name', 'url', 'packages_count')
            ->paginate((int) $request->query('size', '10'));

        return UpstreamResource::collection($upstreams)
            ->toResponse($request);
    }

    public function show(string $upstreamId): JsonResponse
    {
        $this->authorize(Permission::UPSTREAM_READ);

        return response()->json(
            new UpstreamResource($this->query()->findOrFail($upstreamId))
        );
    }

    /**
     * @throws Throwable
     */
    public function store(StoreUpstreamInput $input): JsonResponse
    {
        $this->authorize(Permission::UPSTREAM_CREATE);

        $upstream = $this->storeUpstream->handle($input);

        return response()->json(
            new UpstreamResource($upstream->load(['credentials', 'repositories'])),
            201
        );
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateUpstreamInput $input, string $upstreamId): JsonResponse
    {
        $this->authorize(Permission::UPSTREAM_UPDATE);

        $upstream = $this->updateUpstream->handle(
            upstream: Upstream::query()->findOrFail($upstreamId),
            input: $input,
        );

        return response()->json(
            new UpstreamResource($upstream->load(['credentials', 'repositories']))
        );
    }

    /**
     * @throws Throwable
     */
    public function destroy(string $upstreamId): JsonResponse
    {
        $this->authorize(Permission::UPSTREAM_DELETE);

        $upstream = $this->destroyUpstream->handle(
            Upstream::query()->with('credentials')->findOrFail($upstreamId)
        );

        return response()->json(
            new UpstreamResource($upstream)
        );
    }

    public function test(string $upstreamId): JsonResponse
    {
        $this->authorize(Permission::UPSTREAM_UPDATE);

        /** @var Upstream $upstream */
        $upstream = Upstream::query()->with('credentials')->findOrFail($upstreamId);

        try {
            $metadataUrl = $this->client->metadataUrl($upstream, force: true);
        } catch (UpstreamUnavailableException|UnsupportedUpstreamException $e) {
            return response()->json([
                'ok' => false,
                'metadata_url' => null,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ok' => true,
            'metadata_url' => $metadataUrl,
            'error' => null,
        ]);
    }

    /**
     * @return Builder<Upstream>
     */
    private function query(): Builder
    {
        return Upstream::query()
            ->with(['credentials', 'repositories'])
            ->withCount([
                'packages' => fn (Builder $query) => $query->where('mirror_status', MirrorStatus::OK->value),
                'dists',
            ]);
    }
}
