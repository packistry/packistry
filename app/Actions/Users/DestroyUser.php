<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;

class DestroyUser
{
    public function handle(User $user): User
    {
        $user->delete();

        return $user;
    }
}
