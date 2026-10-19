<?php

namespace Database\Factories;

use App\Models\CommunitySession;
use App\Models\CommunitySessionMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunitySessionMessage> */
class CommunitySessionMessageFactory extends Factory
{
    protected $model = CommunitySessionMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'community_session_id' => CommunitySession::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
            'is_system' => false,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (): array => ['is_system' => true]);
    }
}
