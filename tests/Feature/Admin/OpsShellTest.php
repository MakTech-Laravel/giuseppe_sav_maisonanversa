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
            ->where('items', [])
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
