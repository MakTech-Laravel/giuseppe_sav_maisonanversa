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
        Schema::table('commerce_settings', function (Blueprint $table) {
            $table->boolean('purchases_enabled')->default(true)->after('prices_include_tax');
            $table->boolean('registration_enabled')->default(true)->after('purchases_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commerce_settings', function (Blueprint $table) {
            $table->dropColumn(['purchases_enabled', 'registration_enabled']);
        });
    }
};
