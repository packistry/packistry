<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Ability;
use App\Models\Repository;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = $this->command->ask('Admin email?');
        $name = $this->command->ask('Admin name?');
        $public = $this->command->confirm('Create public root repository?');

        /** @var User $user */
        $user = User::factory()
            ->state([
                'email' => $email,
                'name' => $name,
                'password' => $password = Str::random(),
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
        $this->command->info($password);

        $abilities = array_map(fn (Ability $ability) => $ability->value, Ability::cases());
        $scopes = implode(', ', $abilities);

        if ($this->command->confirm("Create token with scopes [$scopes]?")) {
            $token = $user->createToken('default', $abilities);

            $this->command->info('Token created!');
            $this->command->info($token->plainTextToken);
        }

    }
}
