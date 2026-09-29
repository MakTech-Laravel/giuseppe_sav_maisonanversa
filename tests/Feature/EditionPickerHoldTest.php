<?php

use App\Enums\EditionPieceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\EditionUnavailableException;
use App\Models\EditionPiece;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\OrderFulfillment;
use App\Services\Checkout\ProductCheckout;
use App\Services\Edition\EditionAllocator;
use App\Services\Stripe\CheckoutSessionExpirer;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

function foundingPiece(int $number): EditionPiece
{
    $product = Product::founding();

    return EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel($number))
        ->firstOrFail();
}

test('editions endpoint hides skus and marks the callers reserved piece selectable', function () {
    $product = Product::founding();
    $user = actingAsCheckoutUser();
    $mine = foundingPiece(8);
    $theirs = foundingPiece(9);
    $other = User::factory()->create();

    $mine->update([
        'status' => EditionPieceStatus::Reserved,
        'reserved_by_user_id' => $user->id,
        'reserved_until' => now()->addMinutes(15),
    ]);

    $theirs->update([
        'status' => EditionPieceStatus::Reserved,
        'reserved_by_user_id' => $other->id,
        'reserved_until' => now()->addMinutes(15),
    ]);

    $data = collect($this->getJson(localized('maison.products.editions', [
        'product' => $product->slug,
    ]))->assertOk()->json('data'));

    expect($data->first())->not->toHaveKey('sku')
        ->and($data->firstWhere('edition_number', 8))
        ->toMatchArray([
            'status' => 'reserved',
            'selectable' => true,
        ])
        ->and($data->firstWhere('edition_number', 9))
        ->toMatchArray([
            'status' => 'reserved',
            'selectable' => false,
        ]);
});

test('authenticated user can hold an available piece for fifteen minutes', function () {
    $this->freezeTime();

    $user = actingAsCheckoutUser();
    $product = Product::founding();
    $piece = foundingPiece(10);

    $this->postJson(localized('maison.products.editions.hold', [
        'product' => $product->slug,
        'editionPiece' => $piece->id,
    ]))->assertOk()
        ->assertJsonPath('id', $piece->id)
        ->assertJsonPath('status', 'reserved');

    expect($piece->fresh())
        ->status->toBe(EditionPieceStatus::Reserved)
        ->reserved_by_user_id->toBe($user->id)
        ->and($piece->fresh()->reserved_until?->toDateTimeString())
        ->toBe(now()->addMinutes(15)->toDateTimeString());
});

test('a second user cannot hold an already reserved piece', function () {
    $product = Product::founding();
    $piece = foundingPiece(11);
    $first = User::factory()->create();
    $second = User::factory()->create();

    app(EditionAllocator::class)->holdForUser($first, $product, $piece->id);

    $this->actingAs($second)
        ->postJson(localized('maison.products.editions.hold', [
            'product' => $product->slug,
            'editionPiece' => $piece->id,
        ]))->assertUnprocessable()
        ->assertJsonValidationErrors('edition_piece_id');

    expect(fn () => app(EditionAllocator::class)->holdForUser($second, $product, $piece->id))
        ->toThrow(EditionUnavailableException::class)
        ->and($piece->fresh()->reserved_by_user_id)->toBe($first->id);
});

test('selecting a second number releases the previous hold', function () {
    $user = User::factory()->create();
    $product = Product::founding();
    $first = foundingPiece(14);
    $second = foundingPiece(15);

    $allocator = app(EditionAllocator::class);
    $allocator->holdForUser($user, $product, $first->id);
    $allocator->holdForUser($user, $product, $second->id);

    expect($first->fresh())
        ->status->toBe(EditionPieceStatus::Available)
        ->reserved_by_user_id->toBeNull()
        ->and($second->fresh())
        ->status->toBe(EditionPieceStatus::Reserved)
        ->reserved_by_user_id->toBe($user->id);
});

test('expired picker hold without an order returns to available stock', function () {
    $user = User::factory()->create();
    $product = Product::founding();
    $piece = foundingPiece(16);

    $allocator = app(EditionAllocator::class);
    $allocator->holdForUser($user, $product, $piece->id);
    $piece->update(['reserved_until' => now()->subMinute()]);

    expect($allocator->releaseExpiredHolds())->toBe(1)
        ->and($piece->fresh())
        ->status->toBe(EditionPieceStatus::Available)
        ->reserved_by_user_id->toBeNull()
        ->reserved_until->toBeNull();
});

test('expired checkout hold expires the stripe session and cancels the order', function () {
    $this->mock(CheckoutSessionExpirer::class, function (MockInterface $mock): void {
        $mock->shouldReceive('expire')->once()->with('cs_test_expired_hold');
    });

    $order = Order::factory()->create([
        'stripe_checkout_session_id' => 'cs_test_expired_hold',
    ]);
    Payment::factory()->forOrder($order)->pending()->create([
        'stripe_checkout_session_id' => 'cs_test_expired_hold',
    ]);

    $allocator = app(EditionAllocator::class);
    $held = $allocator->hold($order);
    $held->update(['reserved_until' => now()->subMinute()]);

    expect($allocator->releaseExpiredHolds())->toBe(1)
        ->and($held->fresh()->status)->toBe(EditionPieceStatus::Available)
        ->and($order->fresh()->status)->toBe(OrderStatus::Canceled);
});

test('checkout accepts the callers reserved piece and attaches the order', function () {
    config(['cashier.secret' => 'sk_test_fake']);

    $user = actingAsCheckoutUser();
    $product = Product::founding();
    $piece = foundingPiece(18);

    app(EditionAllocator::class)->holdForUser($user, $product, $piece->id);

    $this->mock(ProductCheckout::class, function (MockInterface $mock): void {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn([
                'url' => 'https://checkout.stripe.com/c/pay/held',
                'session_id' => 'cs_test_held',
            ]);
    });

    $this->post(localized('maison.checkout.store'), checkoutPayload([
        'edition_piece_id' => $piece->id,
    ]))->assertRedirect('https://checkout.stripe.com/c/pay/held');

    $order = Order::query()->first();

    expect($order?->edition_piece_id)->toBe($piece->id)
        ->and($piece->fresh())
        ->status->toBe(EditionPieceStatus::Reserved)
        ->order_id->toBe($order?->id)
        ->reserved_by_user_id->toBe($user->id);
});

test('checkout rejects a piece reserved by another customer', function () {
    $owner = User::factory()->create();
    $product = Product::founding();
    $piece = foundingPiece(19);

    app(EditionAllocator::class)->holdForUser($owner, $product, $piece->id);

    actingAsCheckoutUser();

    $this->post(localized('maison.checkout.store'), checkoutPayload([
        'edition_piece_id' => $piece->id,
    ]))->assertSessionHasErrors('edition_piece_id');
});

test('paid webhook on a canceled expired hold does not allocate a different number', function () {
    Queue::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::Canceled,
        'stripe_checkout_session_id' => 'cs_test_late_pay',
        'edition_piece_id' => null,
        'edition_number' => null,
    ]);
    Payment::factory()->forOrder($order)->pending()->create([
        'stripe_checkout_session_id' => 'cs_test_late_pay',
    ]);

    $availableBefore = EditionPiece::query()
        ->where('product_id', $order->product_id)
        ->where('status', EditionPieceStatus::Available)
        ->count();

    $result = app(OrderFulfillment::class)->markPaidFromSession((object) [
        'id' => 'cs_test_late_pay',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_late',
        'metadata' => ['order_id' => (string) $order->id],
    ]);

    expect($result->status)->toBe(OrderStatus::Canceled)
        ->and($order->fresh())
        ->status->toBe(OrderStatus::Canceled)
        ->edition_number->toBeNull()
        ->and($order->fresh()->latestPayment->status)->toBe(PaymentStatus::Pending)
        ->and(EditionPiece::query()
            ->where('product_id', $order->product_id)
            ->where('status', EditionPieceStatus::Available)
            ->count())->toBe($availableBefore);
});

test('checkout of the last held piece is not treated as sold out', function () {
    config(['cashier.secret' => 'sk_test_fake']);

    $user = actingAsCheckoutUser();
    $product = Product::founding();
    $piece = foundingPiece(21);

    EditionPiece::query()
        ->where('product_id', $product->id)
        ->whereKeyNot($piece->id)
        ->where('status', EditionPieceStatus::Available)
        ->update(['status' => EditionPieceStatus::Allocated->value]);

    app(EditionAllocator::class)->holdForUser($user, $product, $piece->id);

    $this->mock(ProductCheckout::class, function (MockInterface $mock): void {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn([
                'url' => 'https://checkout.stripe.com/c/pay/last-held',
                'session_id' => 'cs_test_last_held',
            ]);
    });

    $this->post(localized('maison.checkout.store'), checkoutPayload([
        'edition_piece_id' => $piece->id,
    ]))->assertRedirect('https://checkout.stripe.com/c/pay/last-held');
});

test('guests cannot hold an edition piece', function () {
    $product = Product::founding();
    $piece = foundingPiece(20);

    $this->postJson(localized('maison.products.editions.hold', [
        'product' => $product->slug,
        'editionPiece' => $piece->id,
    ]))->assertUnauthorized();
});

test('editions endpoint releases expired holds so the number is selectable again', function () {
    $user = actingAsCheckoutUser();
    $product = Product::founding();
    $piece = foundingPiece(22);

    app(EditionAllocator::class)->holdForUser($user, $product, $piece->id);
    $piece->update(['reserved_until' => now()->subMinute()]);

    $data = collect($this->getJson(localized('maison.products.editions', [
        'product' => $product->slug,
    ]))->assertOk()->json('data'));

    expect($piece->fresh())
        ->status->toBe(EditionPieceStatus::Available)
        ->reserved_by_user_id->toBeNull()
        ->and($data->firstWhere('edition_number', 22))
        ->toMatchArray([
            'status' => 'available',
            'selectable' => true,
        ]);
});
