<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can search customers by name and email with pagination', function () {
    User::factory()->create([
        'name' => 'Anna Buyer',
        'email' => 'anna@example.com',
        'type' => UserType::Customer,
    ]);
    User::factory()->create([
        'name' => 'Bram Collector',
        'email' => 'bram@example.com',
        'type' => UserType::Customer,
    ]);
    User::factory()->admin()->create([
        'name' => 'Staff Person',
        'email' => 'staff@example.com',
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.customers.search', [
            'locale' => 'nl',
            'search' => 'anna',
        ]))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.name', 'Anna Buyer')
        ->assertJsonPath('data.0.email', 'anna@example.com')
        ->assertJsonMissing(['email' => 'staff@example.com']);
});

test('customer search returns the next page link for infinite scroll', function () {
    User::factory()->count(25)->create([
        'type' => UserType::Customer,
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.customers.search', [
            'locale' => 'nl',
            'page' => 1,
        ]))
        ->assertOk()
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonCount(20, 'data');

    expect($response->json('links.next'))->toBeString()->toContain('page=2');
});
