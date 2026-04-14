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
        Schema::table('repositories', function (Blueprint $table): void {
            if (Schema::hasColumn('repositories', 'sync_mode')) {
                $table->dropColumn('sync_mode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repositories', function (Blueprint $table): void {
            if (! Schema::hasColumn('repositories', 'sync_mode')) {
                $table->string('sync_mode')
                    ->default('source')
                    ->after('public')
                    ->index();
            }
        });
    }
};
