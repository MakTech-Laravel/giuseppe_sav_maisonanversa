<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('guests cannot access administrator management', function () {
    $this->get(route('admin.admins.index'))->assertRedirect(localized('maison.home', absolute: false));
});

test('users without permission are forbidden from administrators', function () {
    $plain = User::factory()->customer()->create();

    $this->actingAs($plain)->get(route('admin.admins.index'))->assertForbidden();
});

test('super admin can view the administrators list', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.admins.index'))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('admin/admins/index')
                ->has('users.data')
                ->missing('roles')
        );
});

test('administrators list only includes admin type users', function () {
    User::factory()->customer()->create(['email' => 'customer@example.com']);
    $staff = User::factory()->admin()->create(['email' => 'staff@example.com']);
    $staff->assignRole(RoleEnum::ADMIN->value);
    $staff->syncTypeFromRoles();

    $this->actingAs($this->admin)
        ->get(route('admin.admins.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/admins/index')
            ->has('users.data', 2)
        );
});

test('an administrator can be created without assigning roles in the request', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.admins.store'), [
            'name' => 'Jane Staff',
            'email' => 'jane.staff@example.com',
            'password' => 'password123',
        ])
        ->assertRedirect(route('admin.admins.index'));

    $user = User::where('email', 'jane.staff@example.com')->sole();

    expect($user->name)->toBe('Jane Staff')
        ->and($user->type)->toBe(UserType::Admin)
        ->and($user->hasRole(RoleEnum::ADMIN->value))->toBeTrue()
        ->and(Hash::check('password123', $user->password))->toBeTrue();
});

test('a customer cannot be shown on the administrators routes', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.admins.show', ['user' => $customer]))
        ->assertNotFound();
});

test('an administrator can be updated without role payloads', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::ADMIN->value);
    $user->syncTypeFromRoles();

    $this->actingAs($this->admin)
        ->put(route('admin.admins.update', ['user' => $user]), [
            'name' => 'Renamed',
            'email' => $user->email,
            'password' => '',
        ])
        ->assertRedirect(route('admin.admins.index'));

    $user->refresh();

    expect($user->name)->toBe('Renamed')
        ->and($user->type)->toBe(UserType::Admin)
        ->and($user->hasRole(RoleEnum::ADMIN->value))->toBeTrue();
});

test('an administrator can be deleted', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::ADMIN->value);
    $user->syncTypeFromRoles();

    $this->actingAs($this->admin)
        ->delete(route('admin.admins.destroy', ['user' => $user]))
        ->assertRedirect();

    expect(User::find($user->id))->toBeNull();
});
