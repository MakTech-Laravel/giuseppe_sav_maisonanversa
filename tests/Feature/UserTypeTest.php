<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use App\Services\Auth\PostLoginRedirectService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;

test('new users default to the customer type', function () {
    $user = User::factory()->create();

    expect($user->type)->toBe(UserType::Customer)
        ->and($user->isCustomer())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse();
});

test('the admin factory state sets the admin type', function () {
    $user = User::factory()->admin()->create();

    expect($user->type)->toBe(UserType::Admin)
        ->and($user->isAdmin())->toBeTrue();
});

test('syncTypeFromRoles promotes staff roles to admin type', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->create(['type' => UserType::Customer]);
    $user->assignRole(RoleEnum::EDITOR->value);

    $user->syncTypeFromRoles();

    expect($user->fresh()->type)->toBe(UserType::Admin);
});

test('syncTypeFromRoles demotes users without staff roles to customer type', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->admin()->create();
    $user->syncRoles([RoleEnum::USER->value]);

    $user->syncTypeFromRoles();

    expect($user->fresh()->type)->toBe(UserType::Customer);
});

test('post login redirect sends customers to the member dashboard', function () {
    $user = User::factory()->customer()->create();
    $redirects = app(PostLoginRedirectService::class);
    $request = Request::create('/', 'GET', server: [
        'HTTP_ACCEPT_LANGUAGE' => config('maison.default_locale'),
    ]);

    expect($redirects->urlFor($user, $request))->toBe(route('member.dashboard', ['locale' => config('maison.default_locale')]));
});

test('post login redirect sends admin users to the admin dashboard', function () {
    $user = User::factory()->admin()->create();
    $redirects = app(PostLoginRedirectService::class);
    $request = Request::create('/', 'GET', server: [
        'HTTP_ACCEPT_LANGUAGE' => config('maison.default_locale'),
    ]);

    expect($redirects->urlFor($user, $request))->toBe(route('admin.dashboard', ['locale' => config('maison.default_locale')]));
});

test('registration creates a customer account', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'New Member',
        'email' => 'newmember@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect();

    $user = User::query()->where('email', 'newmember@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->type)->toBe(UserType::Customer)
        ->and($user->username)->not->toBeEmpty();
});
