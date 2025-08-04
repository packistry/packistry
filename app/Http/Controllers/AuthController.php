<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AuthenticationSources\HandleAuthenticationSourceCallback;
use App\Actions\Users\Inputs\UpdateMeInput;
use App\Actions\Users\UpdateMe;
use App\Exceptions\AuthenticationSourceException;
use App\Http\Resources\PublicAuthenticationSourceResource;
use App\Http\Resources\UserResource;
use App\Models\AuthenticationSource;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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

        /** @var User $user */
        $user = auth()->user();

        $user->load('authenticationSource');

        return response()->json(
            new UserResource($user)
        );
    }

    public function logout(): JsonResponse
    {
        Auth::guard('web')->logout();

        return response()->json(null, 204);
    }

    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $user->load('authenticationSource');

        return response()->json(
            new UserResource($user)
        );
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

    /**
     * @throws RequestException|ConnectionException
     */
    public function redirect(Request $request, int $sourceId): RedirectResponse
    {
        $source = AuthenticationSource::active()->findOrFail($sourceId);

        return $source->provider($request)->redirect();
    }

    public function callback(Request $request, HandleAuthenticationSourceCallback $callback, int $sourceId): RedirectResponse
    {
        try {
            $user = $callback->handle(
                request: $request,
                source: AuthenticationSource::active()->findOrFail($sourceId)
            );

            Auth::login($user);

            return redirect('/');
        } catch (AuthenticationSourceException $e) {
            $target = Arr::query(['oauth_error' => $e->getMessage()]);

            return redirect("/login?$target");
        } catch (Throwable) {
            return redirect('/login');
        }
    }
}
