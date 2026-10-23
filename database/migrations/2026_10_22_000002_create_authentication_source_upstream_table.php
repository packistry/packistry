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
        Schema::create('authentication_source_upstream', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('authentication_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('upstream_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['authentication_source_id', 'upstream_id'], 'authentication_source_upstream_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authentication_source_upstream');
    }
};
