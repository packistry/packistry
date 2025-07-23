<?php

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
        Schema::table('authentication_sources', function (Blueprint $table) {
            // adding multiple columns back-to-back requires invert ordering
            $table->text('allowed_domains')->default(null)->after('active');
            $table->boolean('allow_registration')->default(false)->after('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authentication_sources', function (Blueprint $table) {
            $table->dropColumn('allow_registration');
            $table->dropColumn('allowed_domains');
        });
    }
};
