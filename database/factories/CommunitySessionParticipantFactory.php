<?php

namespace Database\Factories;

use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunitySessionParticipant> */
class CommunitySessionParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'community_session_id' => CommunitySession::factory(),
            'user_id' => User::factory(),
        ];
    }
}
