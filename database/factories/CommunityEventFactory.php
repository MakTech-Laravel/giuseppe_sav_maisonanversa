<?php

namespace Database\Factories;

use App\Models\CommunityEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunityEvent> */
class CommunityEventFactory extends Factory
{
    public function definition(): array
    {
        $events = [
            [
                'title' => 'Founding Circle Match Morning',
                'description' => 'A relaxed members session with coached warm-up, match rotation, and coffee afterwards.',
                'location' => 'Padel Club Antwerpen',
                'capacity' => 20,
            ],
            [
                'title' => 'Maison Recovery Evening',
                'description' => 'A lighter social evening focused on recovery, conversation, and partner pairings for the next session.',
                'location' => 'Maison Anversa Antwerp',
                'capacity' => 16,
            ],
            [
                'title' => 'Private Court Social',
                'description' => 'An intimate community evening with mixed-level rally play and a short house presentation.',
                'location' => 'Riverside Padel Antwerp',
                'capacity' => 12,
            ],
        ];

        $event = $events[array_rand($events)];

        return [
            'title' => $event['title'],
            'description' => $event['description'],
            'starts_at' => now()->addMonth(),
            'location' => $event['location'],
            'capacity' => $event['capacity'],
        ];
    }
}
