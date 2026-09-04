<?php

use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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

test('paying for a founding-circle-granting product automatically enrolls the buyer', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
    ]);

    expect($order->product->grants_founding_circle)->toBeTrue();

    $session = (object) [
        'id' => 'cs_test_founding_circle',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_founding_circle',
        'metadata' => ['order_id' => (string) $order->id],
    ];

    app(OrderFulfillment::class)->markPaidFromSession($session);

    expect($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeTrue()
        ->and($order->fresh()->edition_number)->not->toBeNull();
});

test('paying for a product that does not grant founding circle does not enroll the buyer', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
        'product_id' => $product->id,
    ]);

    $session = (object) [
        'id' => 'cs_test_non_founding',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_non_founding',
        'metadata' => ['order_id' => (string) $order->id],
    ];

    app(OrderFulfillment::class)->markPaidFromSession($session);

    expect($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse();
});
