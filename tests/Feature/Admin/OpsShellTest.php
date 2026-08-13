<?php

use App\Enums\RoleEnum;
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

test('staff can view orders shell', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/index')
            ->where('commerceConnected', false)
            ->has('orders')
        );
});

test('staff can view an order detail shell', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.orders.show', ['order' => 'MA-2026-0047']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/show')
            ->where('order.id', 'MA-2026-0047')
        );
});

test('missing demo order returns not found', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.orders.show', ['order' => 'MISSING']))
        ->assertNotFound();
});

test('staff can view community shell', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.community.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/community/index')
            ->where('communityConnected', false)
            ->has('items', 3)
        );
});

test('staff can view letter shell', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.letter.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/letter/index')
            ->where('letterConnected', false)
            ->has('subscribers')
        );
});

test('staff can view events shell', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/index')
            ->where('eventsConnected', false)
            ->has('events', 3)
        );
});

test('staff can view an event detail shell', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.events.show', ['event' => 'EVT-LAUNCH-001']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/show')
            ->where('event.id', 'EVT-LAUNCH-001')
            ->has('event.guest_list')
        );
});

test('missing demo event returns not found', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.events.show', ['event' => 'MISSING']))
        ->assertNotFound();
});

test('staff can view founding circle shell', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.circle.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/circle/index')
            ->where('circleConnected', false)
            ->has('members', 3)
        );
});

test('staff can view a circle member detail shell', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.circle.show', ['member' => 'FC-007']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/circle/show')
            ->where('member.id', 'FC-007')
            ->has('member.benefits')
        );
});

test('missing demo circle member returns not found', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.circle.show', ['member' => 'MISSING']))
        ->assertNotFound();
});

test('staff can view heritage product shell', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.heritage.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/heritage/index')
            ->where('heritageConnected', false)
            ->has('inventory.rows')
            ->where('inventory.total', (int) config('maison.edition.total'))
            ->where('inventory.reserved', (int) config('maison.edition.reserved'))
        );
});
