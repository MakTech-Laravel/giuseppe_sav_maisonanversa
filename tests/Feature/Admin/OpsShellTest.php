<?php

use App\Enums\EditionPieceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleEnum;
use App\Jobs\Orders\SendOrderStatusUpdatedBuyerMail;
use App\Listeners\StripeEventListener;
use App\Models\CommunityEvent;
use App\Models\CommunityPost;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Edition\EditionAllocator;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Events\WebhookReceived;
use Maatwebsite\Excel\Facades\Excel;
use Stripe\Service\RefundService;
use Stripe\StripeClient;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can view orders', function () {
    $customer = User::factory()->create(['name' => 'Order Buyer']);
    $product = Product::factory()->create(['name' => 'Court Bag']);
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'name' => $customer->name,
        'email' => $customer->email,
    ]);
    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/index')
            ->has('orders.data', 1)
            ->where('orders.data.0.product_name', 'Court Bag')
            ->where('orders.data.0.customer', 'Order Buyer')
            ->where('orders.data.0.user_id', $customer->id)
            ->where('orders.data.0.payment_status_key', 'pending')
            ->has('filters')
            ->has('statusOptions')
            ->has('paymentStatusOptions')
            ->has('perPageOptions')
        );
});

test('staff can filter orders by search and status', function () {
    $matching = Order::factory()->create([
        'name' => 'Alice Matching',
        'email' => 'alice@example.com',
        'status' => OrderStatus::Paid,
    ]);
    Order::factory()->create([
        'name' => 'Bob Other',
        'email' => 'bob@example.com',
        'status' => OrderStatus::Incomplete,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.orders.index', [
            'search' => 'Alice',
            'status' => OrderStatus::Paid->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/index')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', (string) $matching->id)
            ->where('filters.search', 'Alice')
            ->where('filters.status', OrderStatus::Paid->value)
        );
});

test('staff can filter orders by payment status', function () {
    $pendingOrder = Order::factory()->incomplete()->create();
    Payment::factory()->forOrder($pendingOrder)->create([
        'status' => PaymentStatus::Pending,
    ]);

    $paidOrder = Order::factory()->paid()->create();
    Payment::factory()->forOrder($paidOrder)->paid()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.orders.index', [
            'payment_status' => PaymentStatus::Pending->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/index')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', (string) $pendingOrder->id)
            ->where('orders.data.0.payment_status_key', 'pending')
            ->where('filters.payment_status', PaymentStatus::Pending->value)
        );
});

test('staff can cancel an incomplete order and restore simple stock', function () {
    Bus::fake([SendOrderStatusUpdatedBuyerMail::class]);

    $product = Product::factory()->create(['stock_quantity' => 4]);
    $order = Order::factory()->incomplete()->create([
        'product_id' => $product->id,
        'amount' => $product->amount,
    ]);
    Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.orders.update', ['order' => $order->id]), [
            'status' => 'canceled',
            'message' => 'Bestelling geannuleerd door admin.',
        ])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Canceled)
        ->and($order->fresh()->latestPayment?->status)->toBe(PaymentStatus::Canceled)
        ->and($product->fresh()->stock_quantity)->toBe(5)
        ->and($order->statusEvents()->count())->toBe(1);

    Bus::assertDispatched(SendOrderStatusUpdatedBuyerMail::class);
});

test('staff cannot cancel a paid order', function () {
    Bus::fake([SendOrderStatusUpdatedBuyerMail::class]);

    $order = Order::factory()->paid()->create();

    $this->actingAs($this->admin)
        ->from(route('admin.orders.show', ['order' => $order->id]))
        ->patch(route('admin.orders.update', ['order' => $order->id]), [
            'status' => 'canceled',
            'message' => 'Should not work.',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
    Bus::assertNotDispatched(SendOrderStatusUpdatedBuyerMail::class);
});

test('staff can view an order detail', function () {
    $order = Order::factory()->create();
    $payment = Payment::factory()->forOrder($order)->create([
        'status' => PaymentStatus::Pending,
        'stripe_checkout_session_id' => 'cs_test_admin_detail',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.orders.show', ['order' => $order->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/show')
            ->where('order.id', (string) $order->id)
            ->where('order.payment.id', (string) $payment->id)
            ->where('order.payment.status_key', 'pending')
            ->where('order.payment.stripe_checkout_session_id', 'cs_test_admin_detail')
        );
});

test('missing order returns not found', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.orders.show', ['order' => 99999]))
        ->assertNotFound();
});

test('staff can update order status to shipped', function () {
    Bus::fake([SendOrderStatusUpdatedBuyerMail::class]);

    $order = Order::factory()->paid()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.orders.update', ['order' => $order->id]), [
            'status' => 'shipped',
            'message' => 'Uw pakket is onderweg.',
        ])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped)
        ->and($order->fresh()->shipped_at)->not->toBeNull()
        ->and($order->statusEvents()->count())->toBe(1);

    Bus::assertDispatched(SendOrderStatusUpdatedBuyerMail::class);
});

test('staff can refund a paid order via stripe and restore simple stock', function () {
    config(['cashier.secret' => 'sk_test_fake']);
    Bus::fake([SendOrderStatusUpdatedBuyerMail::class]);

    $product = Product::factory()->create(['stock_quantity' => 4]);
    $order = Order::factory()->paid()->create([
        'product_id' => $product->id,
        'amount' => $product->amount,
        'stripe_payment_intent_id' => 'pi_refund_simple',
    ]);

    $refunds = Mockery::mock(RefundService::class);
    $refunds->shouldReceive('create')
        ->once()
        ->with(['payment_intent' => 'pi_refund_simple'])
        ->andReturn((object) ['id' => 're_test_1']);

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->refunds = $refunds;
    $this->app->bind(StripeClient::class, fn () => $stripe);

    $this->actingAs($this->admin)
        ->patch(route('admin.orders.update', ['order' => $order->id]), [
            'status' => 'refunded',
            'message' => 'Terugbetaling verwerkt.',
        ])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and($product->fresh()->stock_quantity)->toBe(5);
});

test('staff can refund a paid limited edition order and release the piece', function () {
    config(['cashier.secret' => 'sk_test_fake']);
    Bus::fake([SendOrderStatusUpdatedBuyerMail::class]);

    $order = Order::factory()->paid()->create([
        'stripe_payment_intent_id' => 'pi_refund_edition',
    ]);

    $piece = app(EditionAllocator::class)->allocate($order->fresh());

    $refunds = Mockery::mock(RefundService::class);
    $refunds->shouldReceive('create')
        ->once()
        ->with(['payment_intent' => 'pi_refund_edition'])
        ->andReturn((object) ['id' => 're_test_2']);

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->refunds = $refunds;
    $this->app->bind(StripeClient::class, fn () => $stripe);

    $this->actingAs($this->admin)
        ->patch(route('admin.orders.update', ['order' => $order->id]), [
            'status' => 'refunded',
            'message' => 'Terugbetaling verwerkt.',
        ])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and($order->fresh()->edition_piece_id)->toBeNull()
        ->and($piece->fresh()->status)->toBe(EditionPieceStatus::Available)
        ->and($piece->fresh()->order_id)->toBeNull();
});

test('charge.refunded webhook marks the order refunded idempotently', function () {
    $order = Order::factory()->paid()->create([
        'stripe_payment_intent_id' => 'pi_webhook_refund',
    ]);

    $listener = app(StripeEventListener::class);
    $payload = [
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_test_1',
                'object' => 'charge',
                'payment_intent' => 'pi_webhook_refund',
                'refunded' => true,
            ],
        ],
    ];

    $listener->handle(new WebhookReceived($payload));
    $listener->handle(new WebhookReceived($payload));

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
});

test('staff can view community moderation', function () {
    CommunityPost::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.community.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/community/index')
            ->has('posts.data', 1)
            ->has('filters')
        );
});

test('staff can view letter subscribers', function () {
    NewsletterSubscriber::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.letter.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/letter/index')
            ->has('subscribers.data', 1)
            ->has('filters')
        );
});

test('staff can export letter', function () {
    Excel::fake();

    $this->actingAs($this->admin)
        ->get(route('admin.letter.export'))
        ->assertOk();

    Excel::assertDownloaded('heritage-letter.csv');
});

test('staff can view events', function () {
    CommunityEvent::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/index')
            ->has('events.data', 1)
        );
});

test('staff can view an event detail', function () {
    $event = CommunityEvent::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.events.show', ['event' => $event->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/show')
            ->where('event.id', (string) $event->id)
            ->has('event.bookings')
        );
});

test('missing event returns not found', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.events.show', ['event' => 99999]))
        ->assertNotFound();
});

test('staff can view founding circle members', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.circle.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/circle/index')
        );
});

test('staff can view a circle member detail', function () {
    $member = User::factory()->create();
    $member->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $this->actingAs($this->admin)
        ->get(route('admin.circle.show', ['member' => $member->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/circle/show')
            ->where('member.id', (string) $member->id)
            ->has('member.benefits')
        );
});

test('staff can view heritage product inventory', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.heritage.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/heritage/index')
            ->has('pieces.data', 75)
            ->where('pieces.per_page', 75)
            ->where('pieces.total', 100)
            ->where('inventory.total', 100)
            ->where('inventory.reserved', 0)
            ->where('inventory.product_name', 'Heritage No.001 — Founding Edition')
            ->where('product.amount', '249.00')
            ->where('filters.search', '')
            ->where('filters.per_page', 75)
        );
});
