<?php

declare(strict_types=1);

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
        Schema::table('downloads', function (Blueprint $table) {
            $table->date('created_date')->virtualAs('DATE(created_at)')->after('created_at');

            $table->index(['package_id', 'created_date'], 'downloads_package_id_created_at_index');
            $table->index(['created_date'], 'downloads_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropIndex('downloads_package_id_created_at_index');
            $table->dropIndex('downloads_created_at_index');
            $table->dropColumn('created_date');
        });
    }
};
