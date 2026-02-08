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
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();

            $table->string('provider_id')->nullable()->index();

            $table->string('name');
            $table->string('latest_version')->nullable();
            $table->string('type')->index();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('downloads')->default(0);

            $table->timestamps();

            $table->unique(['repository_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
