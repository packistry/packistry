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
        Schema::create('deploy_token_repository', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('deploy_token_id')->constrained('deploy_tokens')->cascadeOnDelete();
            $table->foreignId('repository_id')->constrained('repositories')->cascadeOnDelete();

            $table->index(['deploy_token_id', 'repository_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deploy_token_repository');
    }
};
