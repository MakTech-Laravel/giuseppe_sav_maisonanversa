<?php

namespace Database\Factories;

use App\Enums\ReturnStatus;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderReturn>
 */
class OrderReturnFactory extends Factory
{
    protected $model = OrderReturn::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $requestedAt = now();

        return [
            'number' => 'MA-R-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'order_id' => Order::factory()->paid(),
            'user_id' => User::factory(),
            'status' => ReturnStatus::Requested,
            'reason' => null,
            'comment' => null,
            'maison_pays_return_shipping' => false,
            'expected_refund_amount' => '249.00',
            'refund_amount' => null,
            'refund_reason' => null,
            'stripe_refund_id' => null,
            'carrier' => null,
            'tracking_number' => null,
            'requested_at' => $requestedAt,
            'ship_by' => $requestedAt->copy()->addDays(14),
            'refund_due_by' => $requestedAt->copy()->addDays(14),
            'received_at' => null,
            'resolved_at' => null,
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (): array => [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'expected_refund_amount' => $order->amount,
        ]);
    }
}
