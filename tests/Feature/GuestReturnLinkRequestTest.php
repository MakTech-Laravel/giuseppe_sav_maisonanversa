<?php

use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Mail\Returns\GuestReturnLinkMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    Mail::fake();
});

test('guest checkout buyers can request a signed return link by order reference and email', function () {
    $product = Product::founding();
    $order = Order::factory()->paid()->create([
        'user_id' => null,
        'product_id' => $product->id,
        'email' => 'guest-link@example.com',
        'status' => OrderStatus::Delivered,
        'delivered_on' => now()->subDay()->toDateString(),
        'delivered_at' => now()->subDay(),
        'shipped_at' => now()->subDays(3),
    ]);

    $this->post(route('maison.returns.guest-link', ['locale' => 'nl']), [
        'reference' => $order->reference(),
        'email' => 'guest-link@example.com',
    ])->assertRedirect();

    Mail::assertQueued(GuestReturnLinkMail::class, function (GuestReturnLinkMail $mail) use ($order): bool {
        return $mail->hasTo('guest-link@example.com')
            && $mail->order->is($order);
    });
});

test('guest return link request stays silent when the order does not match', function () {
    $this->post(route('maison.returns.guest-link', ['locale' => 'nl']), [
        'reference' => 'MA-2099-9999',
        'email' => 'nobody@example.com',
    ])->assertRedirect();

    Mail::assertNothingQueued();
});

test('account orders never receive a guest return link mail', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->paid()->create([
        'product_id' => Product::founding()->id,
        'email' => $user->email,
        'status' => OrderStatus::Delivered,
        'delivered_on' => now()->subDay()->toDateString(),
        'delivered_at' => now()->subDay(),
    ]);

    $this->post(route('maison.returns.guest-link', ['locale' => 'nl']), [
        'reference' => $order->reference(),
        'email' => $user->email,
    ])->assertRedirect();

    Mail::assertNothingQueued();
});

test('admins can set and edit the delivery date on an order', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    $order = Order::factory()->paid()->create([
        'status' => OrderStatus::Delivered,
        'delivered_at' => now()->subDays(2),
        'delivered_on' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.delivered-on', ['locale' => 'nl', 'order' => $order->id]), [
            'delivered_on' => now()->subDays(5)->toDateString(),
        ])
        ->assertRedirect();

    expect($order->fresh()->delivered_on?->toDateString())
        ->toBe(now()->subDays(5)->toDateString());
});
