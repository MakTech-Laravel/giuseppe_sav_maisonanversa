<?php

namespace Database\Factories;

use App\Enums\SessionMatchFormat;
use App\Enums\SessionMatchType;
use App\Enums\SessionScoreStatus;
use App\Models\CommunitySession;
use App\Models\CommunitySessionScore;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunitySessionScore> */
class CommunitySessionScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'community_session_id' => CommunitySession::factory(),
            'submitted_by' => User::factory(),
            'match_type' => SessionMatchType::Doubles,
            'format' => SessionMatchFormat::BestOfThree,
            'team_a' => [],
            'team_b' => [],
            'sets' => [['a' => 6, 'b' => 4], ['a' => 6, 'b' => 3]],
            'status' => SessionScoreStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => SessionScoreStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function singles(): static
    {
        return $this->state(fn (): array => [
            'match_type' => SessionMatchType::Singles,
        ]);
    }
}
