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

test('founding circle members can download the passport pdf', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $order = Order::factory()->forUser($user)->create(['status' => OrderStatus::Incomplete]);
    app(EditionAllocator::class)->allocate($order);
    $order->update(['status' => OrderStatus::Paid]);

    $response = $this->actingAs($user)
        ->get(localized('member.passport.pdf'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF');
});

test('registered members without the circle cannot download the passport pdf', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(localized('member.passport.pdf'))
        ->assertForbidden();
});
