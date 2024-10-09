<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Users\Inputs\UpdateMeInput;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class UpdateMe
{
    /**
     * @throws ValidationException
     */
    public function handle(UpdateMeInput $input): User
    {
        /** @var User $user */
        $user = auth()->user() ?? throw new RuntimeException('Can only be used when authenticated');

        if (isset($input->currentPassword, $input->password)) {
            $this->updatePassword($user, $input->currentPassword, $input->password);
        }

        $user->name = $input->name;

        $user->save();

        return $user;
    }

    /**
     * @throws ValidationException
     */
    private function updatePassword(User $user, string $currentPassword, string $password): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->password = $password;
    }
}
