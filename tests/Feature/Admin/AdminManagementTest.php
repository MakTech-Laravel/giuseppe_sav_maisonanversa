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

test('guests cannot access admin management', function () {
    $this->get(route('admin.admins.index'))->assertRedirect(localized('maison.home', absolute: false));
});

test('users without permission are forbidden from admins', function () {
    $plain = User::factory()->customer()->create();

    $this->actingAs($plain)->get(route('admin.admins.index'))->assertForbidden();
});

test('super admin can view the admins list', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.admins.index'))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('admin/admins/index')
                ->has('users.data')
                ->has('roles')
        );
});

test('admins list only includes admin type users', function () {
    User::factory()->customer()->create(['email' => 'customer@example.com']);
    $staff = User::factory()->admin()->create(['email' => 'staff@example.com']);
    $staff->assignRole(RoleEnum::EDITOR->value);
    $staff->syncTypeFromRoles();

    $this->actingAs($this->admin)
        ->get(route('admin.admins.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/admins/index')
            ->has('users.data', 2)
        );
});

test('an admin can be created with staff roles', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.admins.store'), [
            'name' => 'Jane Staff',
            'email' => 'jane.staff@example.com',
            'password' => 'password123',
            'roles' => [RoleEnum::EDITOR->value, RoleEnum::AUTHOR->value],
        ])
        ->assertRedirect(route('admin.admins.index'));

    $user = User::where('email', 'jane.staff@example.com')->sole();

    expect($user->name)->toBe('Jane Staff')
        ->and($user->type)->toBe(UserType::Admin)
        ->and($user->hasRole(RoleEnum::EDITOR->value))->toBeTrue()
        ->and($user->hasRole(RoleEnum::AUTHOR->value))->toBeTrue()
        ->and(Hash::check('password123', $user->password))->toBeTrue();
});

test('creating an admin without staff roles fails', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.admins.create'))
        ->post(route('admin.admins.store'), [
            'name' => 'No Role',
            'email' => 'norole@example.com',
            'password' => 'password123',
            'roles' => [],
        ])
        ->assertRedirect(route('admin.admins.create'))
        ->assertSessionHasErrors('roles');
});

test('a customer cannot be shown on the admins routes', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.admins.show', ['user' => $customer]))
        ->assertNotFound();
});

test('an admin can be updated and roles re-synced', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::VIEWER->value);
    $user->syncTypeFromRoles();

    $this->actingAs($this->admin)
        ->put(route('admin.admins.update', ['user' => $user]), [
            'name' => 'Renamed',
            'email' => $user->email,
            'password' => '',
            'roles' => [RoleEnum::ADMIN->value],
        ])
        ->assertRedirect(route('admin.admins.index'));

    $user->refresh();

    expect($user->name)->toBe('Renamed')
        ->and($user->type)->toBe(UserType::Admin)
        ->and($user->hasRole(RoleEnum::ADMIN->value))->toBeTrue()
        ->and($user->hasRole(RoleEnum::VIEWER->value))->toBeFalse();
});

test('an admin can be deleted', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::EDITOR->value);
    $user->syncTypeFromRoles();

    $this->actingAs($this->admin)
        ->delete(route('admin.admins.destroy', ['user' => $user]))
        ->assertRedirect();

    expect(User::find($user->id))->toBeNull();
});
