<?php

namespace Database\Factories;

use App\Enums\InquiryType;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'type' => InquiryType::Appointment,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->numerify('+32 ### ## ## ##'),
            'subject' => InquiryType::Appointment->subject(),
            'message' => fake()->paragraph(),
            'meta' => null,
            'locale' => fake()->randomElement(['nl', 'en', 'fr']),
            'ip' => '127.0.0.1',
            'device_token' => (string) Str::uuid(),
            'seen_at' => null,
        ];
    }

    public function contact(): static
    {
        return $this->state(fn (): array => [
            'type' => InquiryType::Contact,
            'subject' => InquiryType::Contact->subject(),
        ]);
    }

    public function appointment(): static
    {
        return $this->state(fn (): array => [
            'type' => InquiryType::Appointment,
            'subject' => InquiryType::Appointment->subject(),
            'meta' => [
                'datum' => now()->addWeek()->toDateString(),
                'moment' => 'Ochtend',
            ],
        ]);
    }

    public function consult(): static
    {
        return $this->state(fn (): array => [
            'type' => InquiryType::Consult,
            'subject' => InquiryType::Consult->subject(),
            'meta' => [
                'soort' => 'Videogesprek',
                'moment' => 'Middag',
            ],
        ]);
    }

    public function feedback(): static
    {
        return $this->state(fn (): array => [
            'type' => InquiryType::Feedback,
            'subject' => InquiryType::Feedback->subject(),
            'meta' => [
                'ervaring' => 'Goed',
            ],
        ]);
    }

    public function corner(): static
    {
        return $this->state(fn (): array => [
            'type' => InquiryType::Corner,
            'subject' => InquiryType::Corner->subject(),
            'meta' => [
                'club_name' => fake()->company(),
                'location' => fake()->city(),
                'courts' => '4-6 courts',
                'format' => 'Formaat A — Heritage Corner',
            ],
        ]);
    }

    public function seen(): static
    {
        return $this->state(fn (): array => [
            'seen_at' => now(),
        ]);
    }
}
