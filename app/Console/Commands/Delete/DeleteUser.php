<?php

declare(strict_types=1);

namespace App\Console\Commands\Delete;

use App\Actions\Users\DestroyUser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multisearch;

class DeleteUser extends Command
{
    /** @var string */
    protected $signature = 'delete:user';

    /** @var string|null */
    protected $description = 'Delete a user';

    public function handle(DestroyUser $destroyUser): int
    {
        $users = multisearch(
            label: 'Select the users to delete',
            options: fn (string $value) => User::query()
                ->when($value, fn (Builder $query) => $query->where('name', 'like', "$value%"))
                ->get()
                ->keyBy(fn (User $user): string => (string) $user->id)
                ->map(fn (User $user): string => $user->name)
                ->toArray(),
            required: true,
        );

        if (! confirm('Do you really want to delete the selected users?', default: false)) {
            return self::FAILURE;
        }

        foreach ($users as $user) {
            /** @var User $user */
            $user = User::query()->findOrFail($user);
            $destroyUser->handle($user);

            $this->info("User $user->name deleted");
        }

        return self::SUCCESS;
    }
}
