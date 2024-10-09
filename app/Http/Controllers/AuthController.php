<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Users\Inputs\UpdateMeInput;
use App\Actions\Users\UpdateMe;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController
{
    /**
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json(
            new UserResource(auth()->user())
        );
    }

    public function logout(): JsonResponse
    {
        Auth::guard('web')->logout();

        return response()->json(null, 204);
    }

    /**
     * @throws ValidationException
     */
    public function update(UpdateMe $updateMe, UpdateMeInput $updateMeInput): JsonResponse
    {
        $user = $updateMe->handle($updateMeInput);

        return response()->json(new UserResource($user));
    }
}
