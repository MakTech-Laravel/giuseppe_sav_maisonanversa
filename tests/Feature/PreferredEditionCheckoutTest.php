<?php

use App\Enums\EditionPieceStatus;
use App\Enums\OrderStatus;
use App\Exceptions\EditionUnavailableException;
use App\Jobs\Orders\SendOrderPaidAdminMail;
use App\Jobs\Orders\SendOrderPaidBuyerMail;
use App\Models\EditionPiece;
use App\Models\Order;
use App\Models\Product;
use App\Services\Checkout\OrderFulfillment;
use App\Services\Checkout\ProductCheckout;
use App\Services\Edition\EditionAllocator;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;

test('preferred edition hold reserves the chosen available piece', function () {
    $product = Product::founding();
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel(10))
        ->firstOrFail();

    $order = Order::factory()->create(['product_id' => $product->id]);
    $held = app(EditionAllocator::class)->hold($order, $piece->id);

    expect($held->id)->toBe($piece->id)
        ->and($held->status)->toBe(EditionPieceStatus::Reserved)
        ->and($order->fresh()->edition_piece_id)->toBe($piece->id);
});

test('preferred hold rejects unavailable pieces without fallback', function () {
    $product = Product::founding();
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel(1))
        ->firstOrFail();

    $order = Order::factory()->create(['product_id' => $product->id]);

    expect(fn () => app(EditionAllocator::class)->hold($order, $piece->id))
        ->toThrow(EditionUnavailableException::class);
});

test('limited edition checkout requires edition_piece_id', function () {
    actingAsCheckoutUser();

    $this->post(localized('maison.checkout.store'), checkoutPayload([
        'edition_piece_id' => null,
    ]))->assertSessionHasErrors('edition_piece_id');
});

test('checkout holds the preferred edition piece', function () {
    config(['cashier.secret' => 'sk_test_fake']);

    $product = Product::founding();
    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->where('edition_number', $product->formatEditionLabel(12))
        ->firstOrFail();

    actingAsCheckoutUser();

    $this->mock(ProductCheckout::class, function (MockInterface $mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn([
                'url' => 'https://checkout.stripe.com/c/pay/preferred',
                'session_id' => 'cs_test_preferred',
            ]);
    });

    $this->post(localized('maison.checkout.store'), checkoutPayload([
        'edition_piece_id' => $piece->id,
    ]))->assertRedirect('https://checkout.stripe.com/c/pay/preferred');

    $order = Order::query()->first();

    expect($order?->edition_piece_id)->toBe($piece->id)
        ->and($piece->fresh()->status)->toBe(EditionPieceStatus::Reserved);
});

test('paid fulfillment dispatches buyer and admin mail jobs', function () {
    Bus::fake([SendOrderPaidBuyerMail::class, SendOrderPaidAdminMail::class]);

    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_mail_jobs',
    ]);

    app(OrderFulfillment::class)->markPaidFromSession((object) [
        'id' => 'cs_test_mail_jobs',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_mail',
        'metadata' => ['order_id' => (string) $order->id],
    ]);

    Bus::assertDispatched(SendOrderPaidBuyerMail::class);
    Bus::assertDispatched(SendOrderPaidAdminMail::class);
});
