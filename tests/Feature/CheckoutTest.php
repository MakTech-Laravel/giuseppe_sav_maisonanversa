<?php

use App\Enums\OrderStatus;
use App\Listeners\StripeEventListener;
use App\Models\Order;
use App\Models\User;
use App\Services\Checkout\FoundingEditionCheckout;
use App\Services\Checkout\OrderFulfillment;
use Laravel\Cashier\Events\WebhookReceived;
use Mockery\MockInterface;

test('cashier currency is eur only', function () {
    expect(config('cashier.currency'))->toBe('eur');
    expect(config('maison.checkout.currency'))->toBe('eur');
    expect(config('maison.checkout.amount'))->toBe(24900);
    expect(config('maison.checkout.price_id'))->not->toBeEmpty();
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

test('checkout requires reservation details', function () {
    $this->post(localized('maison.checkout.store'), [])
        ->assertSessionHasErrors(['name', 'email']);
});

test('checkout creates an incomplete order and redirects to stripe', function () {
    config([
        'cashier.secret' => 'sk_test_fake',
        'maison.checkout.price_id' => 'price_test_founding_edition',
    ]);

    $this->mock(FoundingEditionCheckout::class, function (MockInterface $mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn([
                'url' => 'https://checkout.stripe.com/c/pay/test_session',
                'session_id' => 'cs_test_session_123',
            ]);
    });

    $this->post(localized('maison.checkout.store'), [
        'name' => 'Test Buyer',
        'email' => 'buyer@example.com',
        'gift_wrap' => false,
    ])->assertRedirect('https://checkout.stripe.com/c/pay/test_session');

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Incomplete)
        ->and($order->edition_number)->toBeNull()
        ->and($order->edition_piece_id)->not->toBeNull()
        ->and($order->currency)->toBe('eur')
        ->and($order->amount)->toBe(24900)
        ->and($order->stripe_checkout_session_id)->toBe('cs_test_session_123');
});

test('checkout fails gracefully when stripe keys are missing', function () {
    config(['cashier.secret' => null]);

    $this->from(localized('maison.home'))
        ->post(localized('maison.checkout.store'), [
            'name' => 'Test Buyer',
            'email' => 'buyer@example.com',
        ])
        ->assertSessionHasErrors('checkout');

    expect(Order::query()->count())->toBe(0);
});

test('authenticated checkout attaches the user to the order', function () {
    config([
        'cashier.secret' => 'sk_test_fake',
        'maison.checkout.price_id' => 'price_test_founding_edition',
    ]);

    $user = User::factory()->create();

    $this->mock(FoundingEditionCheckout::class, function (MockInterface $mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn([
                'url' => 'https://checkout.stripe.com/c/pay/test_session',
                'session_id' => 'cs_test_auth_456',
            ]);
    });

    $this->actingAs($user)
        ->post(localized('maison.checkout.store'), [
            'name' => $user->name,
            'email' => $user->email,
        ])
        ->assertRedirect('https://checkout.stripe.com/c/pay/test_session');

    expect(Order::query()->first()->user_id)->toBe($user->id);
});

test('cancel marks an incomplete order as canceled', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
        'stripe_checkout_session_id' => 'cs_test_cancel_1',
    ]);

    $this->get(localized('maison.checkout.cancel', [
        'session_id' => 'cs_test_cancel_1',
    ]))->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Canceled);
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
        ->and($fulfilled->stripe_payment_intent_id)->toBe('pi_test_1');

    $again = $fulfillment->markPaidFromSession($paid);
    expect($again->status)->toBe(OrderStatus::Paid);
});

test('stripe webhook listener fulfills paid checkout sessions', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
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
                ],
            ],
        ],
    ]));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->stripe_payment_intent_id)->toBe('pi_test_hook');
});

test('stripe webhook listener marks async payment failures', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Incomplete,
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
                ],
            ],
        ],
    ]));

    expect($order->fresh()->status)->toBe(OrderStatus::Failed);
});
