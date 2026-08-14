<?php

use App\Enums\RoleEnum;
use App\Models\CommunityEvent;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can view orders', function () {
    Order::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/index')
            ->where('commerceConnected', true)
            ->has('orders')
        );
});

test('staff can view an order detail', function () {
    $order = Order::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.orders.show', ['order' => $order->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/show')
            ->where('order.id', (string) $order->id)
        );
});

test('missing order returns not found', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.orders.show', ['order' => 99999]))
        ->assertNotFound();
});

test('staff can view community moderation', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.community.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/community/index')
            ->where('communityConnected', true)
        );
});

test('staff can view letter subscribers', function () {
    NewsletterSubscriber::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.letter.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/letter/index')
            ->has('subscribers')
        );
});

test('staff can view events', function () {
    CommunityEvent::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/index')
            ->where('eventsConnected', true)
            ->has('events', 1)
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
            ->has('event.guest_list')
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
            ->where('circleConnected', true)
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
            ->where('heritageConnected', true)
            ->has('inventory.rows', 100)
            ->where('inventory.total', 100)
            ->where('inventory.reserved', 0)
        );
});
