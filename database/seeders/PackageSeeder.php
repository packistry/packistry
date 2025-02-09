<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Package::factory()
            ->for(Repository::query()->firstOrFail())
            ->count(1000)
            ->create();

        Package::query()->each(function (Package $package) {
            Version::factory()
                ->for($package)
                ->sequence(function (Sequence $sequence) {
                    $number = $sequence->index + 1;

                    return [
                        'name' => "0.$number.0",
                    ];
                })
                ->count(20)
                ->create();
        });
    }
}
