<?php

declare(strict_types=1);

use App\Models\AuthenticationSource;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
    }
};
