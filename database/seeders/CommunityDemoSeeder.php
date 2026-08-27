<?php

namespace Database\Seeders;

use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\CommunityEvent;
use App\Models\CommunityPost;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunityDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $host = User::query()->first() ?? User::factory()->create([
            'name' => 'Founding Host',
            'email' => 'founding.host@example.com',
        ]);

        CommunityPost::factory()->count(2)->create([
            'author_id' => $host->id,
            'status' => 'published',
        ]);

        CommunityEvent::factory()->create([
            'title' => 'Founding Circle Padel Morning',
            'location' => 'Padel Club Antwerpen',
            'starts_at' => now()->addWeek(),
        ]);

        $club = Club::query()->approved()->first() ?? Club::factory()->create();

        $session = CommunitySession::query()->create([
            'host_id' => $host->id,
            'sport' => SessionSport::Padel,
            'club_id' => $club->id,
            'starts_at' => now()->addDays(3)->setTime(10, 30),
            'duration_minutes' => 90,
            'court_status' => SessionCourtStatus::NotBooked,
            'capacity' => 4,
            'level' => SessionLevel::Intermediate,
            'gender' => SessionGender::Everyone,
        ]);

        CommunitySessionParticipant::query()->firstOrCreate([
            'community_session_id' => $session->id,
            'user_id' => $host->id,
        ]);
    }
}
