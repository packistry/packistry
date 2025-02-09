<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Download;
use App\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DownloadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var Collection<int, Package> $packages */
        $packages = Package::query()->with('versions')->whereHas('versions')->get();

        $days = 90;
        $startDate = now()->subDays($days - 1)->startOfDay();

        foreach ($packages as $package) {
            $currentDate = $startDate->clone();

            for ($i = 0; $i < $days; $i++) {
                $dayRows = array_map(fn () => [
                    'version_name' => '0.2.1',
                    'package_id' => $package->id,
                    'version_id' => $package->versions->random()->id,
                    'updated_at' => $currentDate,
                    'created_at' => $currentDate,
                ], array_fill(0, rand(0, 1000), 0));

                Download::query()->insert($dayRows);

                $currentDate->addDay();
            }

        }
    }
}
