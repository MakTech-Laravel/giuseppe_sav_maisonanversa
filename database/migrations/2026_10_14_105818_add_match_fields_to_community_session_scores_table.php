<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_session_scores', function (Blueprint $table) {
            $table->string('match_type')->default('doubles')->after('submitted_by');
            $table->string('format')->default('best_of_3')->after('match_type');
        });
    }

    public function down(): void
    {
        Schema::table('community_session_scores', function (Blueprint $table) {
            $table->dropColumn(['match_type', 'format']);
        });
    }
};
