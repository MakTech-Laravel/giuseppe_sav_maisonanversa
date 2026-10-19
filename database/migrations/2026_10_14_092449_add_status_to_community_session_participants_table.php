<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rows written before join requests existed were instant joins, so the
     * default backfills them as confirmed players.
     */
    public function up(): void
    {
        Schema::table('community_session_participants', function (Blueprint $table) {
            $table->string('status')->default('confirmed')->after('user_id');
            $table->timestamp('decided_at')->nullable()->after('status');

            $table->index(['community_session_id', 'status'], 'csp_session_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('community_session_participants', function (Blueprint $table) {
            $table->dropIndex('csp_session_status_index');
            $table->dropColumn(['status', 'decided_at']);
        });
    }
};
