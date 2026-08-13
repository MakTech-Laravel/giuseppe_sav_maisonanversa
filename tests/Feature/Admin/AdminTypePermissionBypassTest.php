<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('admin-type users pass every permission check while the bypass is enabled', function () {
    config(['maison.admin_type_grants_all_permissions' => true]);

    $staff = User::factory()->admin()->create();
    $staff->assignRole(RoleEnum::VIEWER->value);
    $staff->syncTypeFromRoles();

    expect($staff->type)->toBe(UserType::Admin)
        ->and(Gate::forUser($staff)->allows(PermissionEnum::USERS_DELETE->value))->toBeTrue()
        ->and(Gate::forUser($staff)->allows(PermissionEnum::DASHBOARD_VIEW->value))->toBeTrue();
});

test('customer-type users do not receive the admin-type permission bypass', function () {
    config(['maison.admin_type_grants_all_permissions' => true]);

    $customer = User::factory()->customer()->create();
    $customer->assignRole(RoleEnum::USER->value);

    expect(Gate::forUser($customer)->allows(PermissionEnum::USERS_DELETE->value))->toBeFalse();
});

test('disabling the bypass restores role-based permission checks', function () {
    config(['maison.admin_type_grants_all_permissions' => false]);

    $staff = User::factory()->admin()->create();
    $staff->assignRole(RoleEnum::VIEWER->value);
    $staff->syncTypeFromRoles();

    expect(Gate::forUser($staff)->allows(PermissionEnum::DASHBOARD_VIEW->value))->toBeTrue()
        ->and(Gate::forUser($staff)->allows(PermissionEnum::USERS_DELETE->value))->toBeFalse();
});

test('admin-type bypass does not override model policies for super-admins', function () {
    config(['maison.admin_type_grants_all_permissions' => true]);

    $staff = User::factory()->admin()->create();
    $staff->assignRole(RoleEnum::ADMIN->value);
    $staff->syncTypeFromRoles();

    $super = User::factory()->admin()->create();
    $super->assignRole(RoleEnum::SUPER_ADMIN->value);
    $super->syncTypeFromRoles();

    $this->actingAs($staff)
        ->get(route('admin.admins.edit', ['user' => $super]))
        ->assertForbidden();
});
