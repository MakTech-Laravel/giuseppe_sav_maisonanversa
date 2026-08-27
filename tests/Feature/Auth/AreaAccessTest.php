<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('registered customers cannot visit the admin dashboard', function () {
    $user = User::factory()->customer()->create();
    $user->assignRole(RoleEnum::USER->value);

    $this->actingAs($user)
        ->get(localized('admin.dashboard'))
        ->assertForbidden();
});

test('registered customers cannot visit other admin routes', function () {
    $user = User::factory()->customer()->create();
    $user->assignRole(RoleEnum::USER->value);

    $this->actingAs($user)
        ->get(localized('admin.customers.index'))
        ->assertForbidden();
});

test('staff cannot visit the member dashboard', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    $this->actingAs($user)
        ->get(localized('member.dashboard'))
        ->assertForbidden();
});

test('staff cannot visit nested member routes', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    $this->actingAs($user)
        ->get(localized('member.orders'))
        ->assertForbidden();
});

test('customer login ignores an intended admin dashboard', function () {
    $user = User::factory()->customer()->create();
    $user->assignRole(RoleEnum::USER->value);

    $this->withSession([
        'url.intended' => localized('admin.dashboard', absolute: false),
    ])->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(localized('member.dashboard', absolute: false));
});

test('customer login ignores the legacy dashboard path as intended', function () {
    $user = User::factory()->customer()->create();
    $user->assignRole(RoleEnum::USER->value);

    $this->withSession([
        'url.intended' => '/'.defaultLocale().'/dashboard',
    ])->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(localized('member.dashboard', absolute: false));
});

test('staff login ignores an intended member dashboard', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    $this->withSession([
        'url.intended' => localized('member.dashboard', absolute: false),
    ])->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(localized('admin.dashboard', absolute: false));
});

test('legacy dashboard path redirects customers to the member dashboard', function () {
    $user = User::factory()->customer()->create();
    $user->assignRole(RoleEnum::USER->value);

    $this->actingAs($user)
        ->get('/'.defaultLocale().'/dashboard')
        ->assertRedirect(localized('member.dashboard', absolute: false));
});
