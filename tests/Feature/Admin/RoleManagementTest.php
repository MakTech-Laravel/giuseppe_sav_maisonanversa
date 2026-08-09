<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
});

test('super admin can view the roles list', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('admin/roles/index')
                ->has('roles.data')
        );
});

test('the role editor receives the full permission list', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.roles.create'))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('admin/roles/create')
                ->has('permissions', count(PermissionEnum::cases()))
        );
});

test('a role can be created with grouped permissions', function () {
    $permissions = [
        PermissionEnum::POSTS_INDEX->value,
        PermissionEnum::POSTS_VIEW->value,
        PermissionEnum::POSTS_CREATE->value,
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.roles.store'), [
            'name' => 'content-manager',
            'permissions' => $permissions,
        ])
        ->assertRedirect(route('admin.roles.index'));

    $role = Role::findByName('content-manager');

    expect($role->permissions->pluck('name')->all())
        ->toEqualCanonicalizing($permissions);
});

test('the role name is required and reserved names are rejected', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.roles.store'), [
            'name' => RoleEnum::SUPER_ADMIN->value,
            'permissions' => [],
        ])
        ->assertSessionHasErrors('name');
});

test('a role name must be unique', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.roles.store'), [
            'name' => RoleEnum::EDITOR->value,
            'permissions' => [],
        ])
        ->assertSessionHasErrors('name');
});

test('a role permissions can be updated', function () {
    $role = Role::create(['name' => 'temp', 'guard_name' => 'web']);
    $role->givePermissionTo(PermissionEnum::POSTS_VIEW->value);

    $this->actingAs($this->admin)
        ->put(route('admin.roles.update', $role), [
            'name' => 'temp',
            'permissions' => [
                PermissionEnum::POSTS_CREATE->value,
                PermissionEnum::POSTS_EDIT->value,
            ],
        ])
        ->assertRedirect(route('admin.roles.index'));

    expect($role->fresh()->permissions->pluck('name')->all())
        ->toEqualCanonicalizing([
            PermissionEnum::POSTS_CREATE->value,
            PermissionEnum::POSTS_EDIT->value,
        ]);
});

test('the super admin role cannot be deleted', function () {
    $role = Role::findByName(RoleEnum::SUPER_ADMIN->value);

    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect();

    expect(Role::find($role->id))->not->toBeNull();
});

test('a non-system role can be deleted', function () {
    $role = Role::create(['name' => 'disposable', 'guard_name' => 'web']);

    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect();

    expect(Role::find($role->id))->toBeNull();
});
