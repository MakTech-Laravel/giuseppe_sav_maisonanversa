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

test('staff can open the storefront access form', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.storefront-access.edit', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/storefront-access/edit')
            ->where('settings.purchases_enabled', true)
            ->where('settings.registration_enabled', true)
            ->where('envPurchasesEnabled', true)
            ->where('envRegistrationEnabled', true));
});

test('admin can toggle storefront access flags', function () {
    CommerceSetting::current();

    $this->actingAs($this->admin)
        ->patch(route('admin.storefront-access.update', ['locale' => 'nl']), [
            'purchases_enabled' => false,
            'registration_enabled' => false,
        ])
        ->assertRedirect();

    expect(CommerceSetting::current())
        ->purchases_enabled->toBeFalse()
        ->registration_enabled->toBeFalse();

    $this->get(localized('maison.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('checkout.enabled', false)
            ->where('registration.enabled', false));
});

test('staff without heritage view cannot open storefront access', function () {
    config(['maison.admin_type_grants_all_permissions' => false]);

    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo('dashboard.view');

    $this->actingAs($staff)
        ->get(route('admin.storefront-access.edit', ['locale' => 'nl']))
        ->assertForbidden();
});
