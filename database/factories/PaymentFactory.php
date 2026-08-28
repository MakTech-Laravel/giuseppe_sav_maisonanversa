<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => PaymentStatus::Pending,
            'amount' => '249.00',
            'currency' => 'eur',
            'provider' => 'stripe',
            'stripe_checkout_session_id' => null,
            'stripe_payment_intent_id' => null,
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (): array => [
            'order_id' => $order->id,
            'amount' => $order->amount,
            'currency' => $order->currency,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Pending,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Paid,
            'stripe_checkout_session_id' => 'cs_test_'.fake()->unique()->bothify('??????????'),
            'stripe_payment_intent_id' => 'pi_test_'.fake()->unique()->bothify('??????????'),
        ]);
    }
}
