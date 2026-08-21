<?php

namespace Database\Factories;

use App\Models\CommunitySession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunitySession> */
class CommunitySessionFactory extends Factory
{
    public function definition(): array
    {
        $sessions = [
            [
                'location' => 'Padel Club Antwerpen',
                'capacity' => 4,
                'level' => 'open',
                'notes' => 'Bring a dark change layer for the terrace after play.',
            ],
            [
                'location' => 'Riverside Padel Antwerp',
                'capacity' => 6,
                'level' => 'intermediate',
                'notes' => 'Arrival 15 minutes early for pairings and court assignment.',
            ],
            [
                'location' => 'Founding Circle Court',
                'capacity' => 4,
                'level' => 'all-levels',
                'notes' => 'Short rotation session with emphasis on consistency and touch.',
            ],
        ];

        $session = $sessions[array_rand($sessions)];

        return [
            'host_id' => User::factory(),
            'starts_at' => now()->addDays(7),
            'location' => $session['location'],
            'capacity' => $session['capacity'],
            'level' => $session['level'],
            'notes' => $session['notes'],
        ];
    }
}
