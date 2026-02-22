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
        Schema::create('authentication_source_package', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('authentication_source_id')->constrained(
                'authentication_sources',
                indexName: 'auth_source_package_auth_source_id_foreign'
            )->cascadeOnDelete();
            $table->foreignId('package_id')->constrained(
                'packages',
                indexName: 'auth_source_package_package_id_foreign'
            )->cascadeOnDelete();

            $table->index(['authentication_source_id', 'package_id'], 'source_package_idx');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authentication_source_package');
    }
};
