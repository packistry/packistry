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

enum OAUTH_ERRORS: string
{
    case REGISTRATION_NOT_ALLOWED = 'Registration on this authentication source is not allowed';
    case EXPECT_EMAIL = 'Email not provided';
    case INVALID_DOMAIN = 'Email is not permitted';
};

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
            throw new RuntimeException(OAUTH_ERRORS::EXPECT_EMAIL->value);
        }

        // check if email domain is in the allowed domain list
        if (! $source->check_domain($email)) {
            throw new RuntimeException(OAUTH_ERRORS::INVALID_DOMAIN->value);
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
            // user registration flow

            // check if registration for this method is allowed
            if (! $source->allow_registration) {
                throw new RuntimeException(OAUTH_ERRORS::REGISTRATION_NOT_ALLOWED->value);
            }

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
