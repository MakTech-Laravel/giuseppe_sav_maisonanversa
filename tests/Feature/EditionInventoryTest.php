<?php

use App\Enums\EditionPieceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\EditionSoldOutException;
use App\Models\EditionPiece;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\Payment;
use App\Models\Product;
use App\Services\Checkout\OrderFulfillment;
use App\Services\Edition\EditionAllocator;
use App\Services\Edition\EditionInventory;
use App\Services\Edition\LimitedEditionLedger;
use App\Services\Edition\SimpleStock;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

test('number 001 is archived and never allocated', function () {
    $product = Product::founding();
    $archiveLabel = $product->formatEditionLabel(1);

    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $archiveLabel)
        ->first();

    expect($piece)->not->toBeNull()
        ->and($piece->status)->toBe(EditionPieceStatus::Archive);

    $order = Order::factory()->create();
    $allocated = app(EditionAllocator::class)->allocate($order);

    expect($allocated->sequenceNumber())->toBe(2)
        ->and($order->fresh()->edition_number)->toBe(2);
});

test('allocate is idempotent for the same order', function () {
    $order = Order::factory()->create();
    $allocator = app(EditionAllocator::class);

    $first = $allocator->allocate($order);
    $second = $allocator->allocate($order->fresh());

    expect($first->id)->toBe($second->id)
        ->and(EditionPiece::query()->where('status', EditionPieceStatus::Allocated)->count())->toBe(1);
});

test('expired holds return to available stock', function () {
    $order = Order::factory()->create();
    Payment::factory()->forOrder($order)->pending()->create();
    $allocator = app(EditionAllocator::class);
    $held = $allocator->hold($order);

    $held->update(['reserved_until' => now()->subMinute()]);

    expect($allocator->releaseExpiredHolds())->toBe(1)
        ->and($held->fresh()->status)->toBe(EditionPieceStatus::Available)
        ->and($order->fresh()->status)->toBe(OrderStatus::Canceled)
        ->and($order->fresh()->edition_piece_id)->toBeNull()
        ->and($order->fresh()->latestPayment->status)->toBe(PaymentStatus::Canceled)
        ->and(OrderStatusEvent::query()
            ->where('order_id', $order->id)
            ->where('status', OrderStatus::Canceled)
            ->exists())->toBeTrue();
});

test('the live counter matches allocated pieces', function () {
    $order = Order::factory()->create();
    app(EditionAllocator::class)->allocate($order);
    app(EditionInventory::class)->bust();

    $snapshot = app(EditionInventory::class)->snapshot();

    expect($snapshot['allocated'])->toBe(1)
        ->and($snapshot['reserved'])->toBe(1)
        ->and($snapshot['available'])->toBe(98)
        ->and($snapshot['sellable'])->toBe(99);
});

test('checkout is rejected when sold out', function () {
    EditionPiece::query()
        ->where('status', EditionPieceStatus::Available)
        ->update(['status' => EditionPieceStatus::Allocated]);

    app(EditionInventory::class)->bust();

    actingAsCheckoutUser();

    $this->from(localized('maison.home'))
        ->post(localized('maison.checkout.store'), checkoutPayload([
            'edition_piece_id' => null,
        ]))
        ->assertSessionHasErrors('checkout');
});

test('a second limited product can reuse edition number 001', function () {
    $second = Product::factory()->limitedEdition(5)->create();
    app(LimitedEditionLedger::class)->sync($second);

    $secondLabel = $second->formatEditionLabel(1);
    $founding = Product::founding();
    $foundingArchiveLabel = $founding->formatEditionLabel(1);

    expect(EditionPiece::query()
        ->where('product_id', $second->id)
        ->where('edition_number', $secondLabel)
        ->exists())->toBeTrue()
        ->and(EditionPiece::query()
            ->where('product_id', $second->id)
            ->where('edition_number', $secondLabel)
            ->count())->toBe(1);

    $order = Order::factory()->create(['product_id' => $second->id]);
    $firstAvailable = EditionPiece::query()
        ->where('product_id', $second->id)
        ->where('status', EditionPieceStatus::Available)
        ->orderBy('edition_number')
        ->firstOrFail();
    $allocated = app(EditionAllocator::class)->allocate($order);

    expect($allocated->id)->toBe($firstAvailable->id)
        ->and($allocated->edition_number)->toBe($firstAvailable->edition_number)
        ->and($allocated->product_id)->toBe($second->id)
        ->and(EditionPiece::query()
            ->where('product_id', $founding->id)
            ->where('edition_number', $foundingArchiveLabel)
            ->first()
            ->status)
        ->toBe(EditionPieceStatus::Archive);
});

test('simple stock decrements and sells out independently of numbered editions', function () {
    $product = Product::factory()->create(['stock_quantity' => 1]);
    $stock = app(SimpleStock::class);

    $stock->reserve($product);

    expect($product->fresh()->stock_quantity)->toBe(0)
        ->and(app(EditionInventory::class)->snapshot($product->fresh())['soldOut'])->toBeTrue();

    expect(fn () => $stock->reserve($product->fresh()))->toThrow(EditionSoldOutException::class);
});

test('paid fulfillment assigns an edition number', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_alloc_1',
    ]);

    $paid = (object) [
        'id' => 'cs_test_alloc_1',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_alloc',
        'metadata' => ['order_id' => (string) $order->id],
    ];

    $fulfilled = app(OrderFulfillment::class)->markPaidFromSession($paid);

    expect($fulfilled->status)->toBe(OrderStatus::Paid)
        ->and($fulfilled->edition_number)->toBe(2);
});
