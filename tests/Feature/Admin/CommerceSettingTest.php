<?php

use App\Enums\RoleEnum;
use App\Models\CommerceSetting;
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

test('commerce settings persist shipping estimates and delivery copy', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.commerce.edit'))
        ->patch(route('admin.commerce.update'), [
            'shipping_estimate_min' => '14.00',
            'shipping_estimate_max' => '22.00',
            'shipping_eu_included' => false,
            'default_expected_delivery_label' => 'Late 2027 — subject to production',
            'prices_include_tax' => true,
        ])
        ->assertRedirect();

    $settings = CommerceSetting::current();

    expect($settings->shipping_estimate_min)->toBe('14.00')
        ->and($settings->shipping_estimate_max)->toBe('22.00')
        ->and($settings->shipping_eu_included)->toBeFalse()
        ->and($settings->default_expected_delivery_label)->toBe('Late 2027 — subject to production')
        ->and($settings->prices_include_tax)->toBeTrue();
});

test('staff can view commerce settings', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.commerce.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/commerce/edit')
            ->where('settings.shipping_estimate_min', '12.00')
            ->where('settings.shipping_estimate_max', '18.00')
        );
});

test('shared checkout props include product delivery label', function () {
    $this->get('/nl')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('checkout.productName', 'Heritage No.001 — Founding Edition')
            ->where('checkout.amount', '249.00')
            ->where('commerce.shippingEstimateMin', '12.00')
        );
});
