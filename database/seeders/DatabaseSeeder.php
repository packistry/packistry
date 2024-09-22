<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Repository;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()
            ->state([
                'email' => 'admin@server.com',
                'name' => 'admin',
            ])
            ->create();

        $public = $this->command->confirm('Create public root repository?');

        Repository::factory()
            ->root()
            ->state([
                'public' => $public,
            ])
            ->create();
    }
}
