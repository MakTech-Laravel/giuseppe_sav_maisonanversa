<?php

namespace Database\Factories;

use App\Models\CommunityEvent;
use App\Models\EventRsvp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventRsvp> */
class EventRsvpFactory extends Factory
{
    public function definition(): array
    {
        return [
            'community_event_id' => CommunityEvent::factory(),
            'user_id' => User::factory(),
        ];
    }
}
