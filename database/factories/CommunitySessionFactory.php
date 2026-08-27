<?php

namespace Database\Factories;

use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunitySession> */
class CommunitySessionFactory extends Factory
{
    public function definition(): array
    {
        $notes = [
            'Bring a dark change layer for the terrace after play.',
            'Arrival 15 minutes early for pairings and court assignment.',
            'Short rotation session with emphasis on consistency and touch.',
        ];

        return [
            'host_id' => User::factory(),
            'sport' => SessionSport::Padel,
            'club_id' => Club::factory(),
            'starts_at' => now()->addDays(7),
            'duration_minutes' => 90,
            'court_status' => SessionCourtStatus::NotBooked,
            'capacity' => 4,
            'level' => SessionLevel::Intermediate,
            'gender' => SessionGender::Everyone,
            'notes' => $notes[array_rand($notes)],
            'cancelled_at' => null,
        ];
    }

    public function tennis(): static
    {
        return $this->state(fn (): array => ['sport' => SessionSport::Tennis]);
    }

    public function past(): static
    {
        return $this->state(fn (): array => ['starts_at' => now()->subDays(3)]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['cancelled_at' => now()]);
    }
}
