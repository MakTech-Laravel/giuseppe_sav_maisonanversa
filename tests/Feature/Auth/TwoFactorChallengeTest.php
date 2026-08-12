<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
});

test('two factor challenge redirects to the auth modal when visiting directly', function () {
    $response = $this->get(route('two-factor.login'));

    $response->assertRedirect(localized('maison.home', absolute: false));
    $response->assertSessionHas('open_auth_modal', 'two-factor');
});

test('two factor login stores the pending user id in session', function () {
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->get(route('two-factor.login'))
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('open_auth_modal', 'two-factor')
        ->assertSessionHas('login.id', $user->id);
});
