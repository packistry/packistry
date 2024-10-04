<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\TokenAbility;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = $this->command->ask('Admin email?');
        $name = $this->command->ask('Admin name?');
        $password = $this->command->ask('Admin password?');
        $public = $this->command->confirm('Create public root repository?');

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

        $this->command->info('Admin created!');
        $this->command->info($email);

        $abilities = array_map(fn (TokenAbility $ability) => $ability->value, TokenAbility::cases());
        $scopes = implode(', ', $abilities);

        if ($this->command->confirm("Create token with scopes [$scopes]?")) {
            $token = $user->createToken('default', $abilities);

            $this->command->info('Token created!');
            $this->command->info($token->plainTextToken);
        }

    }
}
