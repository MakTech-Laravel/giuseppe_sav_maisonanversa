<?php

namespace Database\Factories;

use App\Enums\InquiryType;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => InquiryType::Contact,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->numerify('+32 ### ## ## ##'),
            'subject' => fake()->sentence(3),
            'message' => fake()->paragraph(),
            'meta' => null,
            'locale' => fake()->randomElement(['nl', 'en', 'fr']),
            'ip' => '127.0.0.1',
        ];
    }

    public function contact(): static
    {
        return $this->state(fn (): array => [
            'type' => InquiryType::Contact,
        ]);
    }

    public function corner(): static
    {
        return $this->state(fn (): array => [
            'type' => InquiryType::Corner,
            'subject' => 'Club Corner partnership',
            'meta' => [
                'club_name' => fake()->company(),
                'location' => fake()->city(),
                'courts' => '4-6 courts',
                'format' => 'Formaat A — Heritage Corner',
            ],
        ]);
    }
}
