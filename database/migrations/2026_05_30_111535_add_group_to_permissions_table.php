<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a nullable `group` column to the Spatie `permissions` table.
 *
 * Run AFTER the Spatie migration:
 *   php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        Schema::table($table, function (Blueprint $table) {
            $table->string('group')->nullable()->after('guard_name');
        });
    }

    public function down(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('group');
        });
    }
};
