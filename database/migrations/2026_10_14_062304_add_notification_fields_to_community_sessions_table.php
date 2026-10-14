<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_sessions', function (Blueprint $table) {
            $table->text('cancellation_message')->nullable()->after('cancelled_at');
            $table->timestamp('reminder_sent_at')->nullable()->after('cancellation_message');
        });
    }

    public function down(): void
    {
        Schema::table('community_sessions', function (Blueprint $table) {
            $table->dropColumn(['cancellation_message', 'reminder_sent_at']);
        });
    }
};
