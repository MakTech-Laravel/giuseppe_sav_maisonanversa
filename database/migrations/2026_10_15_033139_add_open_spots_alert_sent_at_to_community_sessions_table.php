<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_sessions', function (Blueprint $table) {
            $table->timestamp('open_spots_alert_sent_at')->nullable()->after('reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('community_sessions', function (Blueprint $table) {
            $table->dropColumn('open_spots_alert_sent_at');
        });
    }
};
