<?php

use App\Enums\EditionPieceStatus;
use App\Enums\GuardEnum;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegisterVisibility;
use App\Enums\RoleEnum;
use App\Jobs\SyncOrderToBrevo;
use App\Models\EditionPiece;
use App\Models\FoundingCircleClaim;
use App\Models\FoundingCircleRegisterEntry;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\OrderFulfillment;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

test('order fulfillment is idempotent for paid sessions', function () {
    Queue::fake();

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
    Queue::fake();

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
    Queue::fake();

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

test('the first paid session queues a brevo order list sync once', function () {
    Queue::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_brevo_order',
    ]);

    $session = (object) [
        'id' => 'cs_test_brevo_order',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_brevo_order',
        'metadata' => ['order_id' => (string) $order->id],
    ];

    $fulfillment = app(OrderFulfillment::class);
    $fulfillment->markPaidFromSession($session);
    $fulfillment->markPaidFromSession($session);

    Queue::assertPushed(SyncOrderToBrevo::class, 1);
});

test('an unpaid session does not queue a brevo order list sync', function () {
    Queue::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_brevo_unpaid',
    ]);

    app(OrderFulfillment::class)->markPaidFromSession((object) [
        'id' => 'cs_test_brevo_unpaid',
        'payment_status' => 'unpaid',
        'payment_intent' => null,
        'metadata' => ['order_id' => (string) $order->id],
    ]);

    Queue::assertNotPushed(SyncOrderToBrevo::class);
});

test('canceling an incomplete checkout frees a reserved edition piece', function () {
    $user = User::factory()->create();
    $product = Product::founding();
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel(55))
        ->firstOrFail();

    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
        'product_id' => $product->id,
        'edition_piece_id' => $piece->id,
        'edition_number' => null,
        'stripe_checkout_session_id' => 'cs_test_cancel_reserved',
    ]);

    $piece->update([
        'status' => EditionPieceStatus::Reserved,
        'order_id' => $order->id,
        'reserved_by_user_id' => $user->id,
        'reserved_until' => now()->addMinutes(15),
    ]);

    Payment::factory()->forOrder($order)->pending()->create([
        'stripe_checkout_session_id' => 'cs_test_cancel_reserved',
    ]);

    $result = app(OrderFulfillment::class)->markCanceledBySessionId('cs_test_cancel_reserved');

    expect($result->status)->toBe(OrderStatus::Canceled)
        ->and($order->fresh())
        ->status->toBe(OrderStatus::Canceled)
        ->edition_piece_id->toBeNull()
        ->edition_number->toBeNull()
        ->and($piece->fresh())
        ->status->toBe(EditionPieceStatus::Available)
        ->order_id->toBeNull()
        ->reserved_by_user_id->toBeNull()
        ->and($order->fresh()->latestPayment->status)->toBe(PaymentStatus::Canceled);
});

test('canceling an incomplete order frees a stuck allocated piece and register inscription', function () {
    $user = User::factory()->create();
    $product = Product::founding();
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel(56))
        ->firstOrFail();

    $order = Order::factory()->forUser($user)->create([
        'status' => OrderStatus::Incomplete,
        'product_id' => $product->id,
        'edition_piece_id' => $piece->id,
        'edition_number' => 56,
        'stripe_checkout_session_id' => 'cs_test_cancel_allocated',
    ]);

    $piece->update([
        'status' => EditionPieceStatus::Allocated,
        'order_id' => $order->id,
        'reserved_by_user_id' => null,
        'reserved_until' => null,
        'allocated_at' => now(),
    ]);

    FoundingCircleRegisterEntry::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'order_id' => $order->id,
        'edition_number' => 56,
        'name' => $user->name,
    ]);
    Role::findOrCreate(RoleEnum::FOUNDING_CIRCLE->value, GuardEnum::WEB->value);
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    Payment::factory()->forOrder($order)->pending()->create([
        'stripe_checkout_session_id' => 'cs_test_cancel_allocated',
    ]);

    $result = app(OrderFulfillment::class)->markCanceledBySessionId('cs_test_cancel_allocated');

    expect($result->status)->toBe(OrderStatus::Canceled)
        ->and($order->fresh())
        ->status->toBe(OrderStatus::Canceled)
        ->edition_piece_id->toBeNull()
        ->edition_number->toBeNull()
        ->and($piece->fresh())
        ->status->toBe(EditionPieceStatus::Available)
        ->order_id->toBeNull()
        ->allocated_at->toBeNull()
        ->and(FoundingCircleRegisterEntry::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and($user->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse();
});
