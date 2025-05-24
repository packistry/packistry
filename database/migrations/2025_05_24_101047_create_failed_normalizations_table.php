<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_normalizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('version_id')->constrained('versions')->cascadeOnDelete();
            $table->string('version_name');
            $table->text('error');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_normalizations');
    }
}; 