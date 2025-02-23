<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Users\Inputs\StoreUserInput;
use App\Actions\Users\Inputs\UpdateMeInput;
use App\Actions\Users\Inputs\UpdateUserInput;
use App\Actions\Users\StoreUser;
use App\Actions\Users\UpdateMe;
use App\Actions\Users\UpdateUser;
use App\Enums\Role;
use App\Http\Resources\PublicAuthenticationSourceResource;
use App\Http\Resources\UserResource;
use App\Models\AuthenticationSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

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

        if (! Auth::attempt([...$credentials, 'authentication_source_id' => null])) {
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

    public function sources(): JsonResponse
    {
        $sources = AuthenticationSource::active()
            ->get();

        return response()->json(PublicAuthenticationSourceResource::collection($sources));
    }

    public function redirect(Request $request, int $sourceId): RedirectResponse
    {
        $source = AuthenticationSource::active()->findOrFail($sourceId);

        return $source->provider($request)->redirect();
    }

    public function callback(Request $request, StoreUser $store, UpdateUser $update, int $sourceId): RedirectResponse
    {
        $source = AuthenticationSource::active()->findOrFail($sourceId);

        try {
            $user = $source->provider($request)->user();

            $email = $user->getEmail();

            if ($email === null) {
                throw new RuntimeException('Email not provided');
            }

            $authUser = $source
                ->users()
                ->where('external_id', $user->getId())
                ->first();

            if ($authUser !== null) {
                $update->handle($authUser, new UpdateUserInput(
                    name: $user->getName() ?? '',
                    email: $email,
                ));
            }

            if ($authUser === null) {
                $authUser = $store->handle(
                    new StoreUserInput(
                        name: $user->getName() ?? '',
                        email: $email,
                        role: Role::USER,
                        password: Str::random(),
                        repositories: $source->repositories->pluck('id')->toArray(),
                    )
                );

                $authUser->external_id = $user->getId();
                $authUser->authentication_source_id = $sourceId;
                $authUser->save();
            }

            Auth::login($authUser);

            return redirect('/');
        } catch (Throwable $e) {
            return redirect('/login');
        }
    }
}
