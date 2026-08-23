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

    public function product(): static
    {
        return $this->state(fn (): array => [
            'context' => FaqContext::Product->value,
        ]);
    }

    public function contact(): static
    {
        return $this->state(fn (): array => [
            'context' => FaqContext::Contact->value,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'is_published' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'is_published' => false,
        ]);
    }
}
