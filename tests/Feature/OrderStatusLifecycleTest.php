<?php

use App\Enums\OrderStatus;
use App\Jobs\Orders\SendOrderStatusUpdatedBuyerMail;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\User;
use App\Services\Checkout\OrderStatusService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;

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
