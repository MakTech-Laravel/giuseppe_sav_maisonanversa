<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('home_heroes', 'opening_product_id')) {
            return;
        }

        Schema::table('home_heroes', function (Blueprint $table) {
            $table->foreignId('opening_product_id')
                ->nullable()
                ->after('show_counter')
                ->constrained('products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('home_heroes', 'opening_product_id')) {
            return;
        }

        Schema::table('home_heroes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opening_product_id');
        });
    }
};
