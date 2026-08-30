<?php

use App\Enums\UserGender;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration redirects to the localized home with auth modal flash', function () {
    $response = $this->get(route('register'));

    $response->assertRedirect(localized('maison.home', absolute: false));
    $response->assertSessionHas('open_auth_modal', 'register');
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'gender' => UserGender::Male->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(localized('member.dashboard', absolute: false));

    expect(User::where('email', 'test@example.com')->sole()->gender)->toBe(UserGender::Male);
});

test('registration requires a valid gender', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'nogender@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('gender');

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'badgender@example.com',
        'gender' => 'other',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('gender');

    $this->assertGuest();
});
