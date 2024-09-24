<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Ability;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    protected function user(): ?User
    {
        /** @var User $user */
        $user = Auth::guard('sanctum')->user();

        return $user;
    }

    protected function repository(): Repository
    {
        return once(function () {
            $name = request()->route('repository');

            return Repository::query()
                ->when(
                    $name,
                    fn (Builder $query) => $query->where('name', $name),
                    fn (Builder $query) => $query->whereNull('name')
                )
                ->firstOrFail();
        });
    }

    protected function authorize(Ability $ability): void
    {
        $user = $this->user();

        if (is_null($user) && $this->repository()->public && in_array($ability, Ability::readAbilities())) {
            return;
        }

        if (is_null($user) || ! $user->tokenCan($ability->value)) {
            abort(401);
        }
    }
}
