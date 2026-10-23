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
        Schema::create('upstreams', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->json('only')->nullable();
            $table->json('exclude')->nullable();
            $table->string('metadata_url')->nullable();
            $table->timestamp('root_checked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->timestamps();
        });

        Schema::create('upstream_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('upstream_id')->constrained()->cascadeOnDelete();
            $table->string('host');
            $table->string('type');
            $table->string('username')->nullable();
            $table->text('secret');
            $table->timestamps();

            $table->unique(['upstream_id', 'host'], 'upstream_credentials_unique');
        });

        Schema::create('repository_upstream', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->foreignId('upstream_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['repository_id', 'upstream_id'], 'repository_upstream_unique');
        });

        Schema::create('deploy_token_upstream', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deploy_token_id')->constrained()->cascadeOnDelete();
            $table->foreignId('upstream_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['deploy_token_id', 'upstream_id'], 'deploy_token_upstream_unique');
        });

        Schema::create('upstream_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('upstream_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'upstream_id'], 'upstream_user_unique');
        });

        Schema::create('mirror_metadata', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('upstream_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('dev')->default(false);
            $table->string('body_path');
            $table->string('last_modified')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['upstream_id', 'name', 'dev'], 'mirror_metadata_unique');
        });

        Schema::create('mirror_dists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('upstream_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('reference');
            $table->string('version');
            $table->string('archive_path')->unique();
            $table->string('shasum', 64)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['upstream_id', 'name', 'reference'], 'mirror_dists_unique');
        });

        Schema::table('packages', function (Blueprint $table): void {
            $table->foreignId('upstream_id')->nullable()->after('source_id')->constrained()->nullOnDelete();
            $table->string('mirror_status')->nullable()->after('upstream_id')->index();
        });

        Schema::table('downloads', function (Blueprint $table): void {
            $table->index(['package_id', 'version_name'], 'downloads_package_id_version_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table): void {
            $table->dropIndex('downloads_package_id_version_name_index');
        });

        Schema::table('packages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('upstream_id');
            $table->dropIndex(['mirror_status']);
            $table->dropColumn('mirror_status');
        });

        Schema::dropIfExists('mirror_dists');
        Schema::dropIfExists('mirror_metadata');
        Schema::dropIfExists('upstream_user');
        Schema::dropIfExists('deploy_token_upstream');
        Schema::dropIfExists('repository_upstream');
        Schema::dropIfExists('upstream_credentials');
        Schema::dropIfExists('upstreams');
    }
};
