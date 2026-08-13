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

test('guests cannot access customer management', function () {
    $this->get(route('admin.customers.index'))->assertRedirect(localized('maison.home', absolute: false));
});

test('super admin can view the customers list', function () {
    User::factory()->customer()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('admin/customers/index')
                ->has('customers.data')
        );
});

test('customers list only includes customer type users', function () {
    User::factory()->customer()->count(2)->create();
    $staff = User::factory()->admin()->create();
    $staff->assignRole(RoleEnum::EDITOR->value);
    $staff->syncTypeFromRoles();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/customers/index')
            ->has('customers.data', 2)
        );
});

test('a customer can be created without staff roles', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.customers.store'), [
            'name' => 'Pat Member',
            'email' => 'pat@example.com',
            'password' => 'password123',
        ])
        ->assertRedirect(route('admin.customers.index'));

    $customer = User::where('email', 'pat@example.com')->sole();

    expect($customer->type)->toBe(UserType::Customer)
        ->and($customer->hasRole(RoleEnum::USER->value))->toBeTrue()
        ->and($customer->hasAnyRole(UserType::staffRoleValues()))->toBeFalse()
        ->and(Hash::check('password123', $customer->password))->toBeTrue();
});

test('an admin cannot be shown on the customers routes', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(RoleEnum::EDITOR->value);
    $staff->syncTypeFromRoles();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.show', ['user' => $staff]))
        ->assertNotFound();
});

test('a customer can be updated', function () {
    $customer = User::factory()->customer()->create();
    $customer->assignRole(RoleEnum::USER->value);

    $this->actingAs($this->admin)
        ->put(route('admin.customers.update', ['user' => $customer]), [
            'name' => 'Renamed Customer',
            'email' => $customer->email,
            'password' => '',
        ])
        ->assertRedirect(route('admin.customers.index'));

    $customer->refresh();

    expect($customer->name)->toBe('Renamed Customer')
        ->and($customer->type)->toBe(UserType::Customer)
        ->and($customer->hasRole(RoleEnum::USER->value))->toBeTrue();
});

test('a customer can be deleted', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.customers.destroy', ['user' => $customer]))
        ->assertRedirect();

    expect(User::find($customer->id))->toBeNull();
});
