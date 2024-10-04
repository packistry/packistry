<?php

declare(strict_types=1);

namespace App\Console\Commands\Add;

use App\Actions\Users\Inputs\StoreUserInput;
use App\Actions\Users\StoreUser;
use App\Enums\Role;
use App\Models\Repository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

use function Laravel\Prompts\multisearch;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class AddUser extends Command
{
    /** @var string */
    protected $signature = 'add:user';

    /** @var string|null */
    protected $description = 'Add a user';

    public function handle(StoreUser $storeUser): int
    {
        $name = text(
            label: 'Name',
            placeholder: 'Name',
            required: true
        );

        $email = text(
            label: 'Email',
            placeholder: 'Email',
            required: true
        );

        $password = password(
            label: 'Password',
            placeholder: 'Password',
            required: true
        );

        $role = Role::from(select(
            label: 'Role',
            options: array_map(fn (Role $role) => $role->value, Role::cases()),
            default: app()->isProduction() ? '' : Role::USER->value,
        ));

        $repositories = [];

        if ($role === Role::USER) {
            /** @var string[] $repositories */
            $repositories = multisearch(
                label: 'Select repositories this user will have access to',
                options: fn (string $value) => Repository::query()
                    ->where('public', false)
                    ->when($value, fn (Builder $query) => $query->where('name', 'like', "$value%"))
                    ->get()
                    ->keyBy(fn (Repository $repository): string => (string) $repository->id)
                    ->map(fn (Repository $repository): string => $repository->name ?? 'Root')
                    ->toArray(),
                required: true,
            );
        }

        $storeUser->handle(new StoreUserInput(
            name: $name,
            email: $email,
            role: $role,
            repositories: $repositories,
            password: $password,
        ));

        $this->info('User created');

        return self::SUCCESS;
    }
}
