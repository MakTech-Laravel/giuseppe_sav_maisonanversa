<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('edition_number_prefix')->nullable()->after('edition_total');
            $table->string('edition_number_postfix')->nullable()->after('edition_number_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['edition_number_prefix', 'edition_number_postfix']);
        });
    }
};
