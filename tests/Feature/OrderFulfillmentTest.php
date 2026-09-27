<?php

use App\Enums\OrderStatus;
use App\Enums\RegisterVisibility;
use App\Enums\RoleEnum;
use App\Models\EditionPiece;
use App\Models\FoundingCircleClaim;
use App\Models\FoundingCircleRegisterEntry;
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

test('paying for heritage no.001 inscribes the buyer as a private member on the picked number', function () {
    $user = User::factory()->create();
    $product = Product::founding();
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel(42))
        ->firstOrFail();

    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
        'product_id' => $product->id,
        'edition_piece_id' => $piece->id,
    ]);

    $session = (object) [
        'id' => 'cs_test_founding_circle',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_founding_circle',
        'metadata' => ['order_id' => (string) $order->id],
    ];

    app(OrderFulfillment::class)->markPaidFromSession($session);

    $entry = FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->first();

    expect($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeTrue()
        ->and($order->fresh()->edition_number)->toBe(42)
        ->and($entry)->not->toBeNull()
        ->and($entry->edition_number)->toBe(42)
        ->and($entry->register_visibility)->toBe(RegisterVisibility::Private)
        ->and($entry->register_consent_at)->toBeNull()
        ->and(FoundingCircleClaim::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('paying for another product does not enroll the buyer even when the founding flag is on', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'grants_founding_circle' => true,
    ]);
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

    expect($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse()
        ->and(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->exists())->toBeFalse();
});
