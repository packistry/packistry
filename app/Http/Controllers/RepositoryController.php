<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Repositories\DestroyRepository;
use App\Actions\Repositories\Exceptions\RepositoryAlreadyExistsException;
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
use Illuminate\Validation\ValidationException;
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
            ->paginate((int) $request->query('size', '10'));

        return RepositoryResource::collection($repositories)
            ->toResponse($request);
    }

    /**
     * @throws ValidationException
     */
    public function store(StoreRepositoryInput $input): JsonResponse
    {
        $this->authorize(Permission::REPOSITORY_CREATE);

        try {
            $repository = $this->storeRepository->handle($input);
        } catch (RepositoryAlreadyExistsException) {
            throw ValidationException::withMessages([
                'name' => 'Repository name has already been taken.',
            ]);
        }

        return response()->json(
            new RepositoryResource($repository),
            201
        );
    }

    /**
     * @throws ValidationException
     */
    public function update(UpdateRepositoryInput $input, string $repositoryId): JsonResponse
    {
        $this->authorize(Permission::REPOSITORY_UPDATE);

        try {
            $repository = $this->updateRepository->handle(
                repository: Repository::userScoped()->findOrFail($repositoryId),
                input: $input
            );
        } catch (RepositoryAlreadyExistsException) {
            throw ValidationException::withMessages([
                'name' => 'Repository name has already been taken.',
            ]);
        }

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
