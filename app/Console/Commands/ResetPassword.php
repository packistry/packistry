<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\search;

class ResetPassword extends Command
{
    /** @var string */
    protected $signature = 'reset:password';

    /** @var string|null */
    protected $description = 'Resets a users password';

    public function handle(): int
    {
        $userId = search(
            label: 'Select a user',
            options: fn (string $value) => User::query()
                ->when($value, fn (Builder $query) => $query->where('name', 'like', "$value%"))
                ->get()
                ->keyBy(fn (User $user): string => (string) $user->id)
                ->map(fn (User $user): string => "$user->name ($user->email)")
                ->toArray(),
            required: true,
        );

        $password = password(
            label: 'Updated password',
            required: true,
            validate: ['name' => [new Password(min: 6)]]
        );

        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        $user->password = $password;

        $user->save();

        $this->info('Password reset successful!');

        return self::SUCCESS;
    }
}
