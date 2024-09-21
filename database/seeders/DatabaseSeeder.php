<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Repository;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Version;
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

        Repository::factory()
            ->root()
            ->has(Package::factory()->has(Version::factory()->count(10))->count(10))
            ->create();
    }
}
