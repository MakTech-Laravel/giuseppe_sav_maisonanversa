<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    // A privileged but non-super-admin actor (the `admin` role has full user CRUD).
    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::ADMIN->value);
    $this->admin->syncTypeFromRoles();

    $this->superAdmin = User::factory()->admin()->create();
    $this->superAdmin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->superAdmin->syncTypeFromRoles();
});

function makeSuperAdmin(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    return $user;
}

test('a non super-admin cannot assign the super-admin role when creating an admin', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.admins.store'), [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'password123',
            'roles' => [RoleEnum::SUPER_ADMIN->value],
        ])
        ->assertSessionHasErrors('roles');

    expect(User::role(RoleEnum::SUPER_ADMIN->value)->count())->toBe(1);
});

test('a super-admin can assign the super-admin role', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.admins.store'), [
            'name' => 'New Super',
            'email' => 'newsuper@example.com',
            'password' => 'password123',
            'roles' => [RoleEnum::SUPER_ADMIN->value],
        ])
        ->assertRedirect(route('admin.admins.index'));

    expect(User::where('email', 'newsuper@example.com')->sole()->isSuperAdmin())
        ->toBeTrue();
});

test('a non super-admin cannot open the edit page of a super-admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.admins.edit', ['user' => $this->superAdmin]))
        ->assertForbidden();
});

test('a non super-admin cannot update a super-admin', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.admins.update', ['user' => $this->superAdmin]), [
            'name' => 'Hijacked',
            'email' => $this->superAdmin->email,
            'roles' => [RoleEnum::ADMIN->value],
        ])
        ->assertForbidden();

    expect($this->superAdmin->fresh()->name)->not->toBe('Hijacked');
});

test('a non super-admin cannot delete a super-admin', function () {
    $this->actingAs($this->admin)
        ->delete(route('admin.admins.destroy', ['user' => $this->superAdmin]))
        ->assertForbidden();

    expect(User::find($this->superAdmin->id))->not->toBeNull();
});

test('a super-admin can delete another super-admin', function () {
    $other = makeSuperAdmin();

    $this->actingAs($this->superAdmin)
        ->delete(route('admin.admins.destroy', ['user' => $other]))
        ->assertRedirect();

    expect(User::find($other->id))->toBeNull();
});

test('the last super-admin cannot remove their own super-admin role', function () {
    $this->actingAs($this->superAdmin)
        ->put(route('admin.admins.update', ['user' => $this->superAdmin]), [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'roles' => [RoleEnum::ADMIN->value],
        ])
        ->assertSessionHasErrors('roles');

    expect($this->superAdmin->fresh()->isSuperAdmin())->toBeTrue();
});

test('a super-admin role can be removed once another super-admin exists', function () {
    makeSuperAdmin();

    $this->actingAs($this->superAdmin)
        ->put(route('admin.admins.update', ['user' => $this->superAdmin]), [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'roles' => [RoleEnum::ADMIN->value],
        ])
        ->assertRedirect(route('admin.admins.index'));

    expect($this->superAdmin->fresh()->isSuperAdmin())->toBeFalse();
});

test('the last super-admin cannot be deleted', function () {
    $this->actingAs($this->superAdmin)
        ->delete(route('admin.admins.destroy', ['user' => $this->superAdmin]))
        ->assertRedirect();

    expect(User::find($this->superAdmin->id))->not->toBeNull();
});
