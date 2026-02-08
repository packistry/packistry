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
        Schema::create('authentication_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider');
            $table->string('client_id');
            $table->string('client_secret');
            $table->string('discovery_url')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('default_user_role');
            $table->boolean('active');

            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('authentication_source_id')->nullable()->after('remember_token')->constrained()->nullOnDelete();
        });

        Schema::create('authentication_source_repository', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('authentication_source_id')->constrained('authentication_sources', indexName: 'auth_source_auth_source_id_foreign')->cascadeOnDelete();
            $table->foreignId('repository_id')->constrained('repositories', indexName: 'auth_source_repository_id_foreign')->cascadeOnDelete();

            $table->index(['authentication_source_id', 'repository_id'], 'source_repo_idx');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authentication_sources');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('authentication_source_id');
        });

        Schema::dropIfExists('authentication_source_repository');
    }
};
