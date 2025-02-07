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
        Schema::table('sources', function (Blueprint $table): void {
            $table->boolean('use_name_as_workspace')->default(false)->after('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table): void {
            $table->dropColumn('use_name_as_workspace');
        });
    }
};
