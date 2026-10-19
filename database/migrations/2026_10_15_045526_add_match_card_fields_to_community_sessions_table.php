<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_sessions', function (Blueprint $table) {
            $table->string('share_token', 32)->nullable()->unique()->after('id');
            $table->timestamp('match_card_sent_at')->nullable()->after('open_spots_alert_sent_at');
        });

        DB::table('community_sessions')
            ->whereNull('share_token')
            ->orderBy('id')
            ->pluck('id')
            ->each(fn (int $id) => DB::table('community_sessions')
                ->where('id', $id)
                ->update(['share_token' => Str::random(32)]));
    }

    public function down(): void
    {
        Schema::table('community_sessions', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn(['share_token', 'match_card_sent_at']);
        });
    }
};
