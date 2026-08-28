<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
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
            'product_id' => Product::query()->where('slug', Product::FOUNDING_SLUG)->value('id'),
            'status' => OrderStatus::Incomplete,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'locale' => 'nl',
            'phone' => fake()->optional()->numerify('+32########'),
            'shipping_line1' => fake()->streetAddress(),
            'shipping_line2' => null,
            'shipping_city' => fake()->city(),
            'shipping_postal_code' => fake()->postcode(),
            'shipping_country' => 'BE',
            'edition_number' => null,
            'edition_piece_id' => null,
            'monogram' => fake()->optional(0.3)->lexify('??'),
            'gift_wrap' => false,
            'gift_message' => null,
            'currency' => 'eur',
            'amount' => '249.00',
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
