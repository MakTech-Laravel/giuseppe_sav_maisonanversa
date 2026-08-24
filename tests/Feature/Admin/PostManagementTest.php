<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('the demo admin posts resource is not registered', function () {
    expect(Route::has('admin.posts.index'))->toBeFalse()
        ->and(Route::has('admin.posts.create'))->toBeFalse()
        ->and(Route::has('admin.posts.store'))->toBeFalse()
        ->and(Route::has('admin.posts.show'))->toBeFalse()
        ->and(Route::has('admin.posts.edit'))->toBeFalse()
        ->and(Route::has('admin.posts.update'))->toBeFalse()
        ->and(Route::has('admin.posts.destroy'))->toBeFalse();
});

test('the demo admin posts path is not found', function () {
    $this->actingAs($this->admin)
        ->get('/'.defaultLocale().'/admin/posts')
        ->assertNotFound();
});
