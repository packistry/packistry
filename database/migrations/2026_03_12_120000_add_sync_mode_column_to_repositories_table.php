<?php

declare(strict_types=1);

use App\Enums\RepositorySyncMode;
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
            $table->string('sync_mode')
                ->default(RepositorySyncMode::SOURCE->value)
                ->after('public')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repositories', function (Blueprint $table): void {
            $table->dropColumn('sync_mode');
        });
    }
};
