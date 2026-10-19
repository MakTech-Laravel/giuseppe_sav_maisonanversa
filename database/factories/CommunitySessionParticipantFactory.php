<?php

namespace Database\Factories;

use App\Enums\SessionParticipantStatus;
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
            'status' => SessionParticipantStatus::Confirmed,
            'for_chat_only' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => SessionParticipantStatus::Pending]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => SessionParticipantStatus::Rejected,
            'decided_at' => now(),
        ]);
    }

    public function forChatOnly(): static
    {
        return $this->state(fn (): array => [
            'status' => SessionParticipantStatus::Confirmed,
            'for_chat_only' => true,
            'decided_at' => now(),
        ]);
    }
}
