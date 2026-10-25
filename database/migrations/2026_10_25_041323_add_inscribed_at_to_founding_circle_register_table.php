<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('founding_circle_register', function (Blueprint $table) {
            $table->timestamp('inscribed_at')->nullable()->after('joined_at');
        });

        // Existing rows already appear as Inscribed on the public ledger.
        DB::table('founding_circle_register')
            ->whereNull('inscribed_at')
            ->update([
                'inscribed_at' => DB::raw('COALESCE(joined_at, CURRENT_TIMESTAMP)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('founding_circle_register', function (Blueprint $table) {
            $table->dropColumn('inscribed_at');
        });
    }
};
