<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleEnum;
use App\Listeners\StripeEventListener;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\OrderFulfillment;
use App\Services\Checkout\ProductCheckout;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Cashier\Events\WebhookReceived;
use Mockery\MockInterface;

test('cashier currency is eur only', function () {
    $product = Product::founding();

    expect(config('cashier.currency'))->toBe('eur');
    expect(config('maison.checkout.currency'))->toBe('eur');
    expect($product)->not->toBeNull()
        ->and($product->amount)->toBe('249.00')
        ->and($product->currency)->toBe('eur');
});

test('checkout success and cancel pages are reachable', function () {
    $this->get(localized('maison.checkout.success'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/checkout-success')
            ->where('paid', false));

    $this->get(localized('maison.checkout.cancel'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/checkout-cancel'));
});

test('checkout requires authentication', function () {
    $this->post(localized('maison.checkout.store'), checkoutPayload())
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('open_auth_modal', 'login');

    expect(Order::query()->count())->toBe(0);
});

test('checkout requires reservation details', function () {
    actingAsCheckoutUser();

    $this->post(localized('maison.checkout.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'shipping_line1', 'shipping_city', 'shipping_postal_code', 'shipping_country']);
});

test('checkout creates an incomplete order and redirects to stripe', function () {
    config([
        'cashier.secret' => 'sk_test_fake',
    ]);

    actingAsCheckoutUser();

    $this->mock(ProductCheckout::class, function (MockInterface $mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn([
                'url' => 'https://checkout.stripe.com/c/pay/test_session',
                'session_id' => 'cs_test_session_123',
            ]);
    });

    $this->post(localized('maison.checkout.store'), checkoutPayload())
        ->assertRedirect('https://checkout.stripe.com/c/pay/test_session');

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Incomplete)
        ->and($order->edition_number)->toBeNull()
        ->and($order->edition_piece_id)->not->toBeNull()
        ->and($order->shipping_line1)->toBe('Meir 1')
        ->and($order->shipping_city)->toBe('Antwerpen')
        ->and($order->currency)->toBe('eur')
        ->and($order->amount)->toBe('249.00')
        ->and($order->product_id)->toBe(Product::founding()->id)
        ->and($order->stripe_checkout_session_id)->toBe('cs_test_session_123');

    $payment = $order->latestPayment;

    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->stripe_checkout_session_id)->toBe('cs_test_session_123')
        ->and($payment->amount)->toBe('249.00');
});

test('checkout fails gracefully when stripe keys are missing', function () {
    config(['cashier.secret' => null]);

    actingAsCheckoutUser();

    $this->from(localized('maison.home'))
        ->post(localized('maison.checkout.store'), checkoutPayload())
        ->assertSessionHasErrors('checkout');

    expect(Order::query()->count())->toBe(0);
});

test('authenticated checkout attaches the user to the order', function () {
    config([
        'cashier.secret' => 'sk_test_fake',
    ]);

    $user = User::factory()->create();

    $this->mock(ProductCheckout::class, function (MockInterface $mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn([
                'url' => 'https://checkout.stripe.com/c/pay/test_session',
                'session_id' => 'cs_test_auth_456',
            ]);
    });

    $this->actingAs($user)
        ->post(localized('maison.checkout.store'), checkoutPayload([
            'name' => $user->name,
            'email' => $user->email,
        ]))
        ->assertRedirect('https://checkout.stripe.com/c/pay/test_session');

    expect(Order::query()->first()->user_id)->toBe($user->id);
});

test('checkout accepts product_id for a second published product', function () {
    config([
        'cashier.secret' => 'sk_test_fake',
    ]);

    actingAsCheckoutUser();

    $product = Product::factory()->create([
        'name' => 'Accessory Pack',
        'amount' => '49.00',
        'is_published' => true,
        'stock_quantity' => 5,
    ]);

    $this->mock(ProductCheckout::class, function (MockInterface $mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn([
                'url' => 'https://checkout.stripe.com/c/pay/test_multi',
                'session_id' => 'cs_test_multi_1',
            ]);
    });

    $this->post(localized('maison.checkout.store'), checkoutPayload([
        'product_id' => $product->id,
        'name' => 'Multi Buyer',
        'email' => 'multi@example.com',
        'edition_piece_id' => null,
    ]))->assertRedirect('https://checkout.stripe.com/c/pay/test_multi');

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->product_id)->toBe($product->id)
        ->and($order->amount)->toBe('49.00')
        ->and($product->fresh()->stock_quantity)->toBe(4);
});

test('checkout rejects unpublished product_id', function () {
    actingAsCheckoutUser();

    $product = Product::factory()->create(['is_published' => false]);

    $this->post(localized('maison.checkout.store'), checkoutPayload([
        'product_id' => $product->id,
        'edition_piece_id' => null,
    ]))->assertSessionHasErrors('product_id');

    expect(Order::query()->count())->toBe(0);
});

test('checkout rejects early-access products for non-members', function () {
    actingAsCheckoutUser();

    $product = Product::factory()->create([
        'is_published' => true,
        'public_at' => now()->addHours(24),
    ]);

    $this->post(localized('maison.checkout.store'), checkoutPayload([
        'product_id' => $product->id,
        'edition_piece_id' => null,
    ]))->assertSessionHasErrors('product_id');

    expect(Order::query()->count())->toBe(0);
});

test('founding circle members can check out early-access products', function () {
    config([
        'cashier.secret' => 'sk_test_fake',
    ]);

    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $member = User::factory()->create();
    $member->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    $this->actingAs($member);

    $product = Product::factory()->create([
        'is_published' => true,
        'public_at' => now()->addHours(24),
        'amount' => '79.00',
    ]);

    $this->mock(ProductCheckout::class, function (MockInterface $mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn([
                'url' => 'https://checkout.stripe.com/c/pay/test_early',
                'session_id' => 'cs_test_early',
            ]);
    });

    $this->post(localized('maison.checkout.store'), checkoutPayload([
        'product_id' => $product->id,
        'edition_piece_id' => null,
    ]))->assertRedirect('https://checkout.stripe.com/c/pay/test_early');

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->product_id)->toBe($product->id);
});

test('cancel marks an incomplete order as canceled', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_cancel_1',
    ]);
    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
        'stripe_checkout_session_id' => 'cs_test_cancel_1',
    ]);

    $this->get(localized('maison.checkout.cancel', [
        'session_id' => 'cs_test_cancel_1',
    ]))->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Canceled)
        ->and($order->fresh()->latestPayment->status)->toBe(PaymentStatus::Canceled)
        ->and(OrderStatusEvent::query()
            ->where('order_id', $order->id)
            ->where('status', OrderStatus::Canceled)
            ->exists())->toBeTrue();
});

test('order fulfillment marks paid only when payment_status is paid', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_pay_1',
    ]);

    $fulfillment = app(OrderFulfillment::class);

    $unpaid = (object) [
        'id' => 'cs_test_pay_1',
        'payment_status' => 'unpaid',
        'payment_intent' => null,
        'metadata' => ['order_id' => (string) $order->id],
    ];

    expect($fulfillment->markPaidFromSession($unpaid)->status)->toBe(OrderStatus::Incomplete);

    $paid = (object) [
        'id' => 'cs_test_pay_1',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_1',
        'metadata' => ['order_id' => (string) $order->id],
    ];

    $fulfilled = $fulfillment->markPaidFromSession($paid);

    expect($fulfilled->status)->toBe(OrderStatus::Paid)
        ->and($fulfilled->stripe_payment_intent_id)->toBe('pi_test_1')
        ->and($fulfilled->latestPayment->status)->toBe(PaymentStatus::Paid)
        ->and($fulfilled->latestPayment->stripe_payment_intent_id)->toBe('pi_test_1');

    $again = $fulfillment->markPaidFromSession($paid);
    expect($again->status)->toBe(OrderStatus::Paid);
});

test('stripe webhook listener fulfills paid checkout sessions', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_hook_1',
    ]);
    $payment = Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
        'stripe_checkout_session_id' => 'cs_test_hook_1',
    ]);

    $listener = app(StripeEventListener::class);

    $listener->handle(new WebhookReceived([
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_hook_1',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test_hook',
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'payment_id' => (string) $payment->id,
                ],
            ],
        ],
    ]));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->stripe_payment_intent_id)->toBe('pi_test_hook')
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->stripe_payment_intent_id)->toBe('pi_test_hook');
});

test('paid founding checkout grants founding circle but other products do not', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $member = User::factory()->create();
    $foundingOrder = Order::factory()->forUser($member)->create([
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_circle_1',
    ]);

    app(OrderFulfillment::class)->markPaidFromSession((object) [
        'id' => 'cs_test_circle_1',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_circle',
        'metadata' => ['order_id' => (string) $foundingOrder->id],
    ]);

    expect($member->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeTrue();

    $otherProduct = Product::factory()->create(['grants_founding_circle' => false]);
    $otherUser = User::factory()->create();
    $otherOrder = Order::factory()->forUser($otherUser)->create([
        'product_id' => $otherProduct->id,
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_circle_2',
    ]);

    app(OrderFulfillment::class)->markPaidFromSession((object) [
        'id' => 'cs_test_circle_2',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_other',
        'metadata' => ['order_id' => (string) $otherOrder->id],
    ]);

    expect($otherUser->fresh()->hasRole(RoleEnum::FOUNDING_CIRCLE->value))->toBeFalse()
        ->and($otherOrder->fresh()->edition_number)->toBeNull();
});

test('stripe webhook listener marks async payment failures', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_fail_1',
    ]);
    $payment = Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
        'stripe_checkout_session_id' => 'cs_test_fail_1',
    ]);

    app(StripeEventListener::class)->handle(new WebhookReceived([
        'type' => 'checkout.session.async_payment_failed',
        'data' => [
            'object' => [
                'id' => 'cs_test_fail_1',
                'object' => 'checkout.session',
                'payment_status' => 'unpaid',
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'payment_id' => (string) $payment->id,
                ],
            ],
        ],
    ]));

    expect($order->fresh()->status)->toBe(OrderStatus::Failed)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and(OrderStatusEvent::query()
            ->where('order_id', $order->id)
            ->where('status', OrderStatus::Failed)
            ->exists())->toBeTrue();
});

test('stripe webhook listener cancels expired checkout sessions', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_expire_1',
    ]);
    $payment = Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
        'stripe_checkout_session_id' => 'cs_test_expire_1',
    ]);

    app(StripeEventListener::class)->handle(new WebhookReceived([
        'type' => 'checkout.session.expired',
        'data' => [
            'object' => [
                'id' => 'cs_test_expire_1',
                'object' => 'checkout.session',
                'payment_status' => 'unpaid',
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'payment_id' => (string) $payment->id,
                ],
            ],
        ],
    ]));

    expect($order->fresh()->status)->toBe(OrderStatus::Canceled)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Canceled)
        ->and(OrderStatusEvent::query()
            ->where('order_id', $order->id)
            ->where('status', OrderStatus::Canceled)
            ->exists())->toBeTrue();
});

test('cashier webhook events include checkout session expired and charge refunded', function () {
    expect(config('cashier.webhook.events'))
        ->toContain('checkout.session.expired')
        ->toContain('charge.refunded');
});
