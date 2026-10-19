<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_session_participants', function (Blueprint $table) {
            $table->boolean('for_chat_only')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('community_session_participants', function (Blueprint $table) {
            $table->dropColumn('for_chat_only');
        });
    }
};
