<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Checkout\OrderFulfillment;

test('order fulfillment is idempotent for paid sessions', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
    ]);

    $session = (object) [
        'id' => 'cs_test_idempotent',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_idempotent',
        'metadata' => ['order_id' => (string) $order->id],
    ];

    $fulfillment = app(OrderFulfillment::class);

    $first = $fulfillment->markPaidFromSession($session);
    $second = $fulfillment->markPaidFromSession($session);

    expect($first->isPaid())->toBeTrue()
        ->and($second->isPaid())->toBeTrue()
        ->and(Order::query()->where('status', OrderStatus::Paid)->count())->toBe(1);
});
