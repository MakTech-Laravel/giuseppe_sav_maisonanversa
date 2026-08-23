<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_events', function (Blueprint $table) {
            $table->string('status')->default('opening')->after('capacity');
        });

        $now = now();

        foreach (DB::table('community_events')->orderBy('id')->get() as $event) {
            $startsAt = Carbon::parse($event->starts_at);

            $status = match (true) {
                $startsAt->gt($now) => 'opening',
                $startsAt->gte($now->copy()->startOfDay()) => 'ongoing',
                default => 'closed',
            };

            DB::table('community_events')
                ->where('id', $event->id)
                ->update(['status' => $status]);
        }
    }

    public function down(): void
    {
        Schema::table('community_events', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
