<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'status' => OrderStatus::Incomplete,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->numerify('+32########'),
            'edition_number' => fake()->numberBetween(1, 100),
            'monogram' => fake()->optional(0.3)->lexify('??'),
            'gift_wrap' => false,
            'gift_message' => null,
            'currency' => 'eur',
            'amount' => 24900,
            'stripe_checkout_session_id' => null,
            'stripe_payment_intent_id' => null,
        ];
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Paid,
            'stripe_checkout_session_id' => 'cs_test_'.fake()->unique()->bothify('??????????'),
            'stripe_payment_intent_id' => 'pi_test_'.fake()->unique()->bothify('??????????'),
        ]);
    }

    public function incomplete(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Incomplete,
        ]);
    }
}
