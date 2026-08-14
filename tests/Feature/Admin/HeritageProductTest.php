<?php

use App\Contracts\StripeCatalogGateway;
use App\Enums\RoleEnum;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Tests\Support\FakeStripeCatalogGateway;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can update the founding product name and amount', function () {
    $gateway = new FakeStripeCatalogGateway;
    app()->instance(StripeCatalogGateway::class, $gateway);
    config(['cashier.secret' => 'sk_test_fake']);

    $product = Product::founding();

    $this->actingAs($this->admin)
        ->from(route('admin.heritage.index'))
        ->patch(route('admin.heritage.update', ['product' => $product->id]), [
            'name' => 'Heritage No.001 — Atelier',
            'amount' => '99.99',
        ])
        ->assertRedirect();

    $product->refresh();

    expect($product->name)->toBe('Heritage No.001 — Atelier')
        ->and($product->amount)->toBe('99.99')
        ->and($product->stripe_price_id)->toBe('price_fake_1')
        ->and($gateway->prices['price_fake_1']['amount'])->toBe(9999);
});

test('viewers cannot update the heritage product', function () {
    $viewer = User::factory()->admin()->create();
    $viewer->assignRole(RoleEnum::VIEWER->value);
    $viewer->syncTypeFromRoles();

    $product = Product::founding();

    $this->actingAs($viewer)
        ->patch(route('admin.heritage.update', ['product' => $product->id]), [
            'name' => 'Blocked',
            'amount' => '100.00',
        ])
        ->assertForbidden();
});
