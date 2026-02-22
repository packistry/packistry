<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Users\DestroyUser;
use App\Actions\Users\Inputs\StoreUserInput;
use App\Actions\Users\Inputs\UpdateUserInput;
use App\Actions\Users\StoreUser;
use App\Actions\Users\UpdateUser;
use App\Enums\Permission;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\SearchFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

readonly class UserController extends Controller
{
    public function __construct(
        private StoreUser $storeUser,
        private UpdateUser $updateUser,
        private DestroyUser $destroyUser,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize(Permission::USER_READ);

        $users = QueryBuilder::for(User::query()->with(['repositories', 'packages']))
            ->allowedFilters([
                SearchFilter::allowed(['name', 'email']),
            ])
            ->allowedSorts(['name', 'email', 'role'])
            ->with('authenticationSource')
            ->paginate((int) $request->query('size', '10'));

        return UserResource::collection($users)
            ->toResponse($request);
    }

    public function store(StoreUserInput $input): JsonResponse
    {
        $this->authorize(Permission::USER_CREATE);

        $user = $this->storeUser->handle($input);

        return response()->json(
            new UserResource($user),
            201,
        );
    }

    public function update(UpdateUserInput $input, string $userId): JsonResponse
    {
        $this->authorize(Permission::USER_UPDATE);

        $user = $this->updateUser->handle(
            user: User::query()->findOrFail($userId),
            input: $input
        );

        return response()->json(
            new UserResource($user->load(['repositories', 'packages']))
        );
    }

    public function destroy(string $userId): JsonResponse
    {
        $this->authorize(Permission::USER_DELETE);

        if ((int) $userId === $this->user()->id) {
            abort(403);
        }

        $user = $this->destroyUser->handle(
            User::query()->findOrFail($userId),
        );

        return response()->json(
            new UserResource($user),
        );
    }
}
