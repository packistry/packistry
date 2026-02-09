<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Repositories\DestroyRepository;
use App\Actions\Repositories\Inputs\StoreRepositoryInput;
use App\Actions\Repositories\Inputs\UpdateRepositoryInput;
use App\Actions\Repositories\StoreRepository;
use App\Actions\Repositories\UpdateRepository;
use App\Enums\Permission;
use App\Http\Resources\RepositoryResource;
use App\Models\Repository;
use App\SearchFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class RepositoryController extends Controller
{
    public function __construct(
        private StoreRepository $storeRepository,
        private UpdateRepository $updateRepository,
        private DestroyRepository $destroyRepository,
    ) {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize(Permission::REPOSITORY_READ);

        $repositories = QueryBuilder::for(Repository::userScoped()->withCount('packages'))
            ->allowedFilters([
                SearchFilter::allowed(['name', 'description']),
                AllowedFilter::exact('public'),
            ])
            ->allowedSorts([
                'name',
                'path',
                'packages_count',
            ])
            ->paginate((int) $request->query('size', '10'));

        return RepositoryResource::collection($repositories)
            ->toResponse($request);
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(StoreRepositoryInput $input): JsonResponse
    {
        $this->authorize(Permission::REPOSITORY_CREATE);

        $repository = $this->storeRepository->handle($input);

        return response()->json(
            new RepositoryResource($repository),
            201
        );
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(UpdateRepositoryInput $input, string $repositoryId): JsonResponse
    {
        $this->authorize(Permission::REPOSITORY_UPDATE);

        $repository = $this->updateRepository->handle(
            repository: Repository::userScoped()->findOrFail($repositoryId),
            input: $input
        );

        return response()->json(
            new RepositoryResource($repository)
        );
    }

    public function destroy(string $repositoryId): JsonResponse
    {
        $this->authorize(Permission::REPOSITORY_DELETE);

        $repository = $this->destroyRepository->handle(
            repository: Repository::userScoped()->findOrFail($repositoryId)
        );

        return response()->json(
            new RepositoryResource($repository),
        );
    }
}
