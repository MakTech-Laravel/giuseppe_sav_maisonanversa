<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

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

test('created administrators receive the admin role automatically', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.admins.store'), [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'password' => 'password123',
        ])
        ->assertRedirect(route('admin.admins.index'));

    $created = User::where('email', 'newstaff@example.com')->sole();

    expect($created->hasRole(RoleEnum::ADMIN->value))->toBeTrue()
        ->and($created->isSuperAdmin())->toBeFalse();
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
            'password' => '',
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

test('the last super-admin cannot be deleted', function () {
    $this->actingAs($this->superAdmin)
        ->delete(route('admin.admins.destroy', ['user' => $this->superAdmin]))
        ->assertRedirect();

    expect(User::find($this->superAdmin->id))->not->toBeNull();
});
