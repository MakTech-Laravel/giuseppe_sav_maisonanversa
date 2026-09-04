<?php

use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Models\Order;
use App\Models\User;
use App\Services\Edition\EditionAllocator;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('members see a dash when they have no edition', function () {
    $user = User::factory()->create(['name' => 'Circle Member']);

    $this->actingAs($user)
        ->get(localized('member.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('member/dashboard')
            ->where('member.name', 'Circle Member')
            ->where('member.editionNumber', '—')
        );
});

test('founding circle members can view passport data from their order', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $order = Order::factory()->forUser($user)->create(['status' => OrderStatus::Incomplete]);
    app(EditionAllocator::class)->allocate($order);
    $order->update(['status' => OrderStatus::Paid]);

    $this->actingAs($user)
        ->get(localized('member.passport'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('member/passport')
            ->where('passport.editionNumber', '002')
            ->has('passport.pages', 4)
        );
});

test('admin-assigned circle members without an order see a friendly empty state instead of a 404', function (
    string $route,
    string $component,
    string $prop,
) {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $this->actingAs($user)
        ->get(localized($route))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component($component)
            ->where($prop, null)
        );
})->with([
    ['member.heritage', 'member/heritage', 'heritage'],
    ['member.passport', 'member/passport', 'passport'],
    ['member.circle', 'member/circle', 'card'],
]);

test('registered members without the circle cannot open the passport', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(localized('member.passport'))
        ->assertForbidden();
});

test('members only see their own orders', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $order = Order::factory()->forUser($owner)->create();

    $this->actingAs($stranger)
        ->get(localized('member.orders.show', ['order' => $order->id]))
        ->assertForbidden();
});
