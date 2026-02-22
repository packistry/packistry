<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Users\Inputs\UpdateUserInput;
use App\Enums\Role;
use App\Exceptions\EmailAlreadyTakenException;
use App\Models\User;

class UpdateUser
{
    /**
     * @throws EmailAlreadyTakenException
     */
    public function handle(User $user, UpdateUserInput $input): User
    {
        if (is_string($input->email)) {
            if (User::isEmailInUse($input->email, exclude: $user->id)) {
                throw new EmailAlreadyTakenException;
            }

            $user->email = $input->email;
        }

        if (is_string($input->name)) {
            $user->name = $input->name;
        }

        if (is_string($input->password)) {
            $user->password = $input->password;
        }

        if ($input->role instanceof Role) {
            $user->role = $input->role;
        }

        $user->save();

        if (is_array($input->repositories)) {
            $user->repositories()->sync($input->repositories);
        }

        if (is_array($input->packages)) {
            $user->packages()->sync($input->packages);
        }

        return $user;
    }
}
