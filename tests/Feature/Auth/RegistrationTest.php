<?php

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
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(localized('member.dashboard', absolute: false));
});
