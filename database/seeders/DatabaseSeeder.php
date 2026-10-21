<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\TokenAbility;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $command = $this->command;

        if ($command === null) {
            throw new RuntimeException('The database seeder must be run from the console.');
        }

        $email = $command->ask('Admin email?');
        $name = $command->ask('Admin name?');
        $password = $command->ask('Admin password?');
        $public = $command->confirm('Create public root repository?');

        /** @var User $user */
        $user = User::factory()
            ->state([
                'email' => $email,
                'name' => $name,
                'password' => $password,
                'role' => Role::ADMIN,
            ])
            ->create();

        Repository::factory()
            ->root()
            ->state([
                'public' => $public,
            ])
            ->create();

        $command->info('Admin created!');
        $command->info($email);

        $abilities = array_map(fn (TokenAbility $ability) => $ability->value, TokenAbility::cases());
        $scopes = implode(', ', $abilities);

        if ($command->confirm("Create token with scopes [$scopes]?")) {
            $token = $user->createToken('default', $abilities);

            $command->info('Token created!');
            $command->info($token->plainTextToken);
        }

    }
}
