<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });

        DB::table('orders')->update([
            'amount' => DB::raw('amount / 100.0'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('orders')->update([
            'amount' => DB::raw('ROUND(amount * 100)'),
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
        });
    }
};
