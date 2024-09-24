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
            $table->foreignId('repository_id')->constrained()->onDelete('cascade');
            $table->foreignId('source_id')->nullable()->constrained('sources')->onDelete('set null');

            $table->string('name')->unique();
            $table->string('type')->index();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('downloads')->default(0);

            $table->timestamps();
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
