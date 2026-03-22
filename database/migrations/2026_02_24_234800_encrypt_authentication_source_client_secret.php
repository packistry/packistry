<?php

declare(strict_types=1);

use App\Models\AuthenticationSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('authentication_sources', function (Blueprint $table) {
            $table->text('client_secret')->change();
        });

        AuthenticationSource::query()->each(function (AuthenticationSource $source) {
            $source->client_secret = encrypt($source->client_secret);
            $source->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        AuthenticationSource::query()->each(function (AuthenticationSource $source) {
            $source->client_secret = decrypt($source->client_secret);
            $source->save();
        });

        Schema::table('authentication_sources', function (Blueprint $table) {
            $table->string('client_secret')->change();
        });
    }
};
