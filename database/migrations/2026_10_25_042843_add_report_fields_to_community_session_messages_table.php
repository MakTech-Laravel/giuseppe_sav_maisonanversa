<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_session_messages', function (Blueprint $table) {
            $table->foreignId('reported_by_id')->nullable()->after('is_system')->constrained('users')->nullOnDelete();
            $table->timestamp('reported_at')->nullable()->after('reported_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('community_session_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reported_by_id');
            $table->dropColumn('reported_at');
        });
    }
};
