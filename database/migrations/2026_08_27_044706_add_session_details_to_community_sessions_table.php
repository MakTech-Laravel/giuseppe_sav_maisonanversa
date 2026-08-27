<?php

use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionSport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('community_sessions', function (Blueprint $table) {
            $table->string('sport')->default(SessionSport::Padel->value)->after('host_id')->index();
            $table->foreignId('club_id')->nullable()->after('sport')->constrained('clubs')->nullOnDelete();
            $table->unsignedSmallInteger('duration_minutes')->default(90)->after('starts_at');
            $table->timestamp('ends_at')->nullable()->after('duration_minutes')->index();
            $table->string('court_status')->default(SessionCourtStatus::NotBooked->value)->after('ends_at');
            $table->string('gender')->default(SessionGender::Everyone->value)->after('level');
            $table->timestamp('cancelled_at')->nullable()->after('notes');
        });

        $this->backfillLevels();

        DB::table('community_sessions')
            ->whereNull('ends_at')
            ->update([
                'ends_at' => DB::raw($this->addMinutesExpression()),
            ]);

        Schema::table('community_sessions', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_sessions', function (Blueprint $table) {
            $table->string('location')->default('');
        });

        Schema::table('community_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('club_id');
            $table->dropColumn([
                'sport',
                'duration_minutes',
                'ends_at',
                'court_status',
                'gender',
                'cancelled_at',
            ]);
        });
    }

    /**
     * Legacy sessions stored free-text levels; map them onto the enum.
     */
    private function backfillLevels(): void
    {
        $map = [
            'beginner' => SessionLevel::Beginner->value,
            'intermediate' => SessionLevel::Intermediate->value,
            'advanced' => SessionLevel::Advanced->value,
            'open' => SessionLevel::OpenToAll->value,
            'all-levels' => SessionLevel::OpenToAll->value,
        ];

        foreach ($map as $legacy => $value) {
            DB::table('community_sessions')->where('level', $legacy)->update(['level' => $value]);
        }

        DB::table('community_sessions')
            ->whereNotIn('level', array_column(SessionLevel::cases(), 'value'))
            ->update(['level' => SessionLevel::OpenToAll->value]);
    }

    /**
     * Driver-specific "starts_at + duration_minutes" expression for the backfill.
     */
    private function addMinutesExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "datetime(starts_at, '+' || duration_minutes || ' minutes')",
            'pgsql' => "starts_at + (duration_minutes * interval '1 minute')",
            default => 'DATE_ADD(starts_at, INTERVAL duration_minutes MINUTE)',
        };
    }
};
