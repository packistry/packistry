<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\User;

readonly class Controller
{
    public function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    public function authorize(Permission $permission): void
    {
        if ($this->user()->can($permission)) {
            return;
        }

        abort(403);
    }
}
