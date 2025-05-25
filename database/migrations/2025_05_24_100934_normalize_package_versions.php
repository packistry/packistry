<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Package;
use App\Normalizer;

return new class extends Migration
{
    public function up(): void
    {
        $packages = DB::table('packages')
            ->join('versions', 'packages.id', '=', 'versions.package_id')
            ->select('packages.id as package_id', 'versions.id as version_id', 'versions.name as version_name')
            ->get();
        
        foreach ($packages as $package) {
            try {
                $normalizedVersion = Normalizer::version($package->version_name);
                
                if ($normalizedVersion !== $package->version_name) {

                    $existingVersion = DB::table('versions')
                        ->where('package_id', $package->package_id)
                        ->where('name', $normalizedVersion)
                        ->where('id', '!=', $package->version_id)
                        ->first();
                    
                    if ($existingVersion !== null) {

                        DB::table('versions')
                            ->where('id', $package->version_id)
                            ->delete();
                    } else {

                        DB::table('versions')
                            ->where('id', $package->version_id)
                            ->update(['name' => $normalizedVersion]);
                    }
                }
            } catch (\App\Exceptions\VersionNotFoundException $e) {
                DB::table('failed_normalizations')->insert([
                    'package_id' => $package->package_id,
                    'version_id' => $package->version_id,
                    'version_name' => $package->version_name,
                    'error' => $e->getMessage(),
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // This migration cannot be reversed as it modifies data
    }
}; 