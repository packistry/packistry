<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Users\Inputs\StoreUserInput;
use App\Enums\Permission;
use App\Exceptions\EmailAlreadyTakenException;
use App\Models\User;
use Illuminate\Support\Str;

class StoreUser
{
    /**
     * @throws EmailAlreadyTakenException
     */
    public function handle(StoreUserInput $input): User
    {
        if (User::isEmailInUse($input->email)) {
            throw new EmailAlreadyTakenException;
        }

        $user = new User;

        $user->name = $input->name;
        $user->email = $input->email;
        $user->role = $input->role;
        $user->password = $input->password ?? Str::random();

        $user->save();

        if ($user->canNot(Permission::UNSCOPED) && is_array($input->repositories)) {
            $user->repositories()->sync($input->repositories);
        }

        return $user;
    }
}
