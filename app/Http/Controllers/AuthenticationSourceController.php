<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AuthenticationSources\DestroyAuthenticationSource;
use App\Actions\AuthenticationSources\Inputs\StoreAuthenticationSourceInput;
use App\Actions\AuthenticationSources\Inputs\UpdateAuthenticationSourceInput;
use App\Actions\AuthenticationSources\StoreAuthenticationSource;
use App\Actions\AuthenticationSources\UpdateAuthenticationSource;
use App\Enums\Permission;
use App\Exceptions\InvalidDiscoveryUrlException;
use App\Http\Resources\AuthenticationSourceResource;
use App\Models\AuthenticationSource;
use App\SearchFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

readonly class AuthenticationSourceController extends Controller
{
    public function __construct(
        private StoreAuthenticationSource $store,
        private DestroyAuthenticationSource $destroy,
        private UpdateAuthenticationSource $update,
    ) {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize(Permission::AUTHENTICATION_SOURCE_READ);

        $tokens = QueryBuilder::for(
            AuthenticationSource::query()
        )
            ->allowedFilters([
                SearchFilter::allowed(['name']),
            ])
            ->allowedSorts([
                'name',
                'active',
            ])
            ->with(['repositories', 'packages'])
            ->paginate((int) $request->query('size', '10'));

        return AuthenticationSourceResource::collection($tokens)
            ->toResponse($request);
    }

    /**
     * @throws InvalidDiscoveryUrlException|Throwable
     */
    public function store(StoreAuthenticationSourceInput $input): JsonResponse
    {
        $this->authorize(Permission::AUTHENTICATION_SOURCE_CREATE);

        $source = $this->store->handle($input);

        return response()->json(
            new AuthenticationSourceResource($source),
            201
        );
    }

    /**
     * @throws InvalidDiscoveryUrlException|Throwable
     */
    public function update(UpdateAuthenticationSourceInput $input, int $id): JsonResponse
    {
        $this->authorize(Permission::AUTHENTICATION_SOURCE_UPDATE);

        $source = $this->update->handle(
            source: AuthenticationSource::query()->findOrFail($id),
            input: $input
        );

        return response()->json(
            new AuthenticationSourceResource($source)
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize(Permission::AUTHENTICATION_SOURCE_DELETE);

        $source = $this->destroy->handle(
            AuthenticationSource::query()->findOrFail($id)
        );

        return response()->json(
            new AuthenticationSourceResource($source)
        );
    }
}
