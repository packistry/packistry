<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeployTokens\DestroyDeployToken;
use App\Actions\DeployTokens\Inputs\StoreDeployTokenInput;
use App\Actions\DeployTokens\StoreDeployToken;
use App\Enums\Permission;
use App\Http\Resources\DeployTokenResource;
use App\Models\DeployToken;
use App\SearchFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

readonly class DeployTokenController extends Controller
{
    public function __construct(
        private StoreDeployToken $storeDeployToken,
        private DestroyDeployToken $destroyDeployToken,
    ) {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize(Permission::DEPLOY_TOKEN_READ);

        $tokens = QueryBuilder::for(
            DeployToken::query()
                ->with('token')
        )
            ->allowedFilters([
                SearchFilter::allowed(['name']),
            ])
            ->paginate((int) $request->query('size', '10'));

        return DeployTokenResource::collection($tokens)
            ->toResponse($request);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreDeployTokenInput $input): JsonResponse
    {
        $this->authorize(Permission::DEPLOY_TOKEN_CREATE);

        [$token, $accessToken] = $this->storeDeployToken->handle($input);

        $token->load('token');

        return response()->json([
            'token' => (new DeployTokenResource($token)),
            'plain_text' => $accessToken->plainTextToken,
        ], 201);
    }

    /**
     * @throws Throwable
     */
    public function destroy(string $token): JsonResponse
    {
        $this->authorize(Permission::DEPLOY_TOKEN_DELETE);

        $token = DeployToken::query()->findOrFail($token);

        $token = $this->destroyDeployToken->handle($token);

        return response()->json(
            new DeployTokenResource($token),
        );
    }
}
