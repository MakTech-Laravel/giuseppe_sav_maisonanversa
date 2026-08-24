<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('guests are redirected to the localized home page', function () {
    $this->get(localized('admin.dashboard'))
        ->assertRedirect(localized('maison.home', absolute: false));
});

test('authenticated staff can visit the admin dashboard', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    $this->actingAs($user)
        ->get(localized('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats', 3)
            ->where('stats.0.key', 'Klanten')
            ->where('stats.0.hintKey', 'Lid-accounts')
            ->where('stats.1.key', 'Beheerders')
            ->where('stats.2.key', 'Journal')
            ->has('recentCustomers')
            ->where('staffName', $user->name)
            ->where('locale', defaultLocale())
        );
});

test('admin dashboard is reachable under each maison locale', function (string $locale) {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    $this->actingAs($user)
        ->get(route('admin.dashboard', ['locale' => $locale]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('locale', $locale)
            ->where('stats.0.key', 'Klanten')
        );
})->with(['nl', 'en', 'fr']);

test('users without dashboard permission are forbidden', function () {
    $user = User::factory()->customer()->create();

    $this->actingAs($user)
        ->get(localized('admin.dashboard'))
        ->assertForbidden();
});

test('legacy dashboard path redirects to the admin dashboard', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    $this->actingAs($user)
        ->get('/'.defaultLocale().'/dashboard')
        ->assertRedirect('/'.defaultLocale().'/admin/dashboard');
});
