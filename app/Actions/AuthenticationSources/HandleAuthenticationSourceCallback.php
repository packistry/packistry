<?php

declare(strict_types=1);

namespace App\Actions\AuthenticationSources;

use App\Actions\Users\Inputs\StoreUserInput;
use App\Actions\Users\Inputs\UpdateUserInput;
use App\Actions\Users\StoreUser;
use App\Actions\Users\UpdateUser;
use App\Enums\Role;
use App\Exceptions\EmailAlreadyTakenException;
use App\Models\AuthenticationSource;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

readonly class HandleAuthenticationSourceCallback
{
    public function __construct(
        private StoreUser $store,
        private UpdateUser $update
    ) {
        //
    }

    /**
     * @throws RequestException
     * @throws EmailAlreadyTakenException
     * @throws ConnectionException
     */
    public function handle(Request $request, AuthenticationSource $source): User
    {
        $providedUser = $source->provider($request)->user();

        $email = $providedUser->getEmail();

        if ($email === null) {
            throw new RuntimeException('Email not provided');
        }

        $user = $source
            ->users()
            ->where('external_id', $providedUser->getId())
            ->first();

        if ($user !== null) {
            $this->update->handle($user, new UpdateUserInput(
                name: $providedUser->getName() ?? '',
                email: $email,
            ));
        }

        if ($user === null) {
            $user = $this->store->handle(
                new StoreUserInput(
                    name: $providedUser->getName() ?? '',
                    email: $email,
                    role: Role::USER,
                    password: Str::random(),
                    repositories: $source->repositories->pluck('id')->toArray(),
                )
            );

            $user->external_id = $providedUser->getId();
            $user->authentication_source_id = $source->id;
            $user->save();
        }

        return $user;
    }
}
