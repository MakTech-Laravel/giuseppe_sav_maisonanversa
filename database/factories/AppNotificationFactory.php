<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppNotification>
 */
class AppNotificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => NotificationType::Info,
            'title' => fake()->sentence(4),
            'message' => fake()->sentence(12),
            'data' => null,
            'created_by' => null,
        ];
    }

    public function type(NotificationType $type): static
    {
        return $this->state(fn (): array => [
            'type' => $type,
        ]);
    }
}
