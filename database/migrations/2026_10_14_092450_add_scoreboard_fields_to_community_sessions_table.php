<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_sessions', function (Blueprint $table) {
            $table->timestamp('scoreboard_locked_at')->nullable()->after('reminder_sent_at');
            $table->timestamp('finished_at')->nullable()->after('scoreboard_locked_at');
        });
    }

    public function down(): void
    {
        Schema::table('community_sessions', function (Blueprint $table) {
            $table->dropColumn(['scoreboard_locked_at', 'finished_at']);
        });
    }
};
