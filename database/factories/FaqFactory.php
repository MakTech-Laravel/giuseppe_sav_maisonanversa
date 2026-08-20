<?php

namespace Database\Factories;

use App\Enums\FaqContext;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'context' => fake()->randomElement([
                FaqContext::Product->value,
                FaqContext::Contact->value,
            ]),
            'question' => fake()->sentence(),
            'answer' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_published' => true,
        ];
    }
}
