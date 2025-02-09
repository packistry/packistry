<?php

declare(strict_types=1);

use App\Console\Commands\RecalculateTotalDownloads;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('versions', function (Blueprint $table) {
            $table->unsignedBigInteger('total_downloads')->default(0)->after('order');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->renameColumn('downloads', 'total_downloads');
        });

        Artisan::call(RecalculateTotalDownloads::class);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('versions', function (Blueprint $table) {
            $table->dropColumn('total_downloads');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->renameColumn('total_downloads', 'downloads');
        });
    }
};
