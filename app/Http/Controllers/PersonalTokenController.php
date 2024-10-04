<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PersonalTokens\DestroyPersonalToken;
use App\Actions\PersonalTokens\Inputs\StorePersonalTokenInput;
use App\Actions\PersonalTokens\StorePersonalToken;
use App\Enums\Permission;
use App\Http\Resources\PersonalTokenResource;
use App\Models\Token;
use App\Models\User;
use App\SearchFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

readonly class PersonalTokenController extends Controller
{
    public function __construct(
        private StorePersonalToken $storePersonalToken,
        private DestroyPersonalToken $destroyPersonalToken,
    ) {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize(Permission::PERSONAL_TOKEN_READ);

        /** @var User $user */
        $user = auth()->user();

        $tokens = QueryBuilder::for(
            $user->tokens()
        )
            ->allowedFilters([
                SearchFilter::allowed(['name']),
            ])
            ->paginate((int) $request->query('size', '10'));

        return PersonalTokenResource::collection($tokens)
            ->toResponse($request);
    }

    public function store(StorePersonalTokenInput $input): JsonResponse
    {
        $this->authorize(Permission::PERSONAL_TOKEN_CREATE);

        /** @var User $user */
        $user = auth()->user();

        $token = $this->storePersonalToken->handle($user, $input);

        return response()->json([
            'token' => (new PersonalTokenResource($token->accessToken)),
            'plain_text' => $token->plainTextToken,
        ], 201);
    }

    public function destroy(int $token): JsonResponse
    {
        $this->authorize(Permission::PERSONAL_TOKEN_DELETE);

        /** @var User $user */
        $user = auth()->user();

        /** @var Token $token */
        $token = $user->tokens()->findOrFail($token);

        $this->destroyPersonalToken->handle($token);

        return response()->json(
            new PersonalTokenResource($token)
        );
    }
}
