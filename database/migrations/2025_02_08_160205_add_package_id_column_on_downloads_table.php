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
        Schema::create('downloads_new', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('token_id')->nullable()->constrained()->nullOnDelete();
            $table->string('version_name');
            $table->ipAddress('ip')->nullable();

            $table->timestamps();
        });

        DB::statement(<<<'sql'
            INSERT INTO downloads_new
            SELECT
                downloads.id AS id,
                versions.package_id AS package_id,
                downloads.version_id AS version_id,
                downloads.token_id AS token_id,
                versions.name AS version_name,
                downloads.ip AS ip,
                downloads.created_at AS created_at,
                downloads.updated_at AS updated_at
            FROM
                downloads
            LEFT JOIN versions ON versions.id = downloads.version_id
        sql);

        Schema::dropIfExists('downloads');
        Schema::rename('downloads_new', 'downloads');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('downloads_new', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('token_id')->nullable()->constrained()->nullOnDelete();
            $table->ipAddress('ip')->nullable();

            $table->timestamps();
        });

        DB::statement(<<<'sql'
            INSERT INTO downloads_new
            SELECT
                downloads.id AS id,
                downloads.version_id AS version_id,
                downloads.token_id AS token_id,
                downloads.ip AS ip,
                downloads.created_at AS created_at,
                downloads.updated_at AS updated_at
            FROM
                downloads
        sql);

        Schema::dropIfExists('downloads');
        Schema::rename('downloads_new', 'downloads');
    }
};
