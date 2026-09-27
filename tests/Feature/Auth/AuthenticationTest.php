<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

test('login redirects to the localized home with auth modal flash', function () {
    $response = $this->get(route('login'));

    $response->assertRedirect(localized('maison.home', absolute: false));
    $response->assertSessionHas('open_auth_modal', 'login');
});

test('bare admin urls open the login modal for guests', function (string $path) {
    $this->get($path)
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('open_auth_modal', 'login');
})->with(['/admin', '/admin/login']);

test('bare admin urls send staff to the localized admin path', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertRedirect('/'.defaultLocale().'/admin/dashboard');

    $this->actingAs($admin)
        ->get('/admin/circle/register')
        ->assertRedirect('/'.defaultLocale().'/admin/circle/register');

    $this->actingAs($admin)
        ->get('/admin/login')
        ->assertRedirect(localized('admin.dashboard', absolute: false));
});

test('bare admin urls send customers to the member dashboard', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get('/admin')
        ->assertRedirect(localized('member.dashboard', absolute: false));
});

test('authenticated visitors are sent to their dashboard instead of the login modal', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('login'));

    $response->assertRedirect(localized('member.dashboard', absolute: false));
    $response->assertSessionMissing('open_auth_modal');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(localized('member.dashboard', absolute: false));
});

test('staff users authenticate to the admin dashboard', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->syncTypeFromRoles();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(localized('admin.dashboard', absolute: false));
});

test('users with two factor enabled are redirected to the auth modal', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(localized('maison.home', absolute: false));
    $response->assertSessionHas('open_auth_modal', 'two-factor');
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withUnencryptedCookie(config('maison.locale_cookie'), 'en')
        ->post(route('logout'));

    $response->assertRedirect(route('maison.home', ['locale' => 'en'], absolute: false));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
