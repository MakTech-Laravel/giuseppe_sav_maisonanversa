<?php

use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Jobs\Orders\SendOrderStatusUpdatedBuyerMail;
use App\Models\AppNotification;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Checkout\OrderStatusService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Stripe\Service\RefundService;
use Stripe\StripeClient;

test('admin can move a paid order to processing with a buyer message', function () {
    Bus::fake([SendOrderStatusUpdatedBuyerMail::class]);

    $order = Order::factory()->paid()->create();
    $admin = User::factory()->admin()->create();

    $updated = app(OrderStatusService::class)->transition(
        $order,
        OrderStatus::Processing,
        'We starten de voorbereiding.',
        $admin,
    );

    expect($updated->status)->toBe(OrderStatus::Processing)
        ->and($updated->processing_at)->not->toBeNull()
        ->and(OrderStatusEvent::query()->where('order_id', $order->id)->count())->toBe(1);

    Bus::assertDispatched(SendOrderStatusUpdatedBuyerMail::class);
});

test('member order detail includes shipping and status events', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->paid()->create([
        'shipping_line1' => 'Meir 1',
        'shipping_city' => 'Antwerpen',
        'shipping_postal_code' => '2000',
        'shipping_country' => 'BE',
    ]);

    OrderStatusEvent::factory()->forOrder($order)->create([
        'status' => OrderStatus::Paid,
        'message' => 'Betaling ontvangen.',
    ]);

    $this->actingAs($user)
        ->get(localized('member.orders.show', ['order' => $order->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('member/order-show')
            ->where('order.shipping.line1', 'Meir 1')
            ->where('order.events.0.message', 'Betaling ontvangen.')
        );
});

test('refunding an order notifies the buyer with an order inbox row', function () {
    Bus::fake([SendOrderStatusUpdatedBuyerMail::class]);
    config(['cashier.secret' => 'sk_test_fake']);

    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->paid()->create([
        'stripe_payment_intent_id' => 'pi_notify_refund',
    ]);
    $admin = User::factory()->admin()->create();

    $refunds = Mockery::mock(RefundService::class);
    $refunds->shouldReceive('create')
        ->once()
        ->andReturn((object) ['id' => 're_notify_1']);

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->refunds = $refunds;
    app()->bind(StripeClient::class, fn () => $stripe);

    app(OrderStatusService::class)->transition(
        $order,
        OrderStatus::Refunded,
        'Terugbetaling verwerkt.',
        $admin,
    );

    expect(AppNotification::query()->where('type', NotificationType::Order)->where('title', 'Bestelling terugbetaald')->exists())->toBeTrue()
        ->and(UserNotification::query()->where('user_id', $user->id)->exists())->toBeTrue();

    app()->setLocale('en');

    expect(__('Bestelling terugbetaald'))->toBe('Order refunded');
});
