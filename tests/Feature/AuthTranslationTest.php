<?php

use App\Models\User;

test('auth.failed is translated for every maison locale', function (string $locale, string $expected) {
    app()->setLocale($locale);

    expect(__('auth.failed'))->toBe($expected);
})->with([
    ['nl', 'Deze gegevens komen niet overeen met onze records.'],
    ['en', 'These credentials do not match our records.'],
    ['fr', 'Ces identifiants ne correspondent pas à nos enregistrements.'],
]);

test('failed login returns a translated error for the session locale', function (string $locale, string $expected) {
    $user = User::factory()->create();

    $this->withSession(['locale' => $locale])
        ->from(route('maison.home', ['locale' => $locale]))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertSessionHasErrors([
            'email' => $expected,
        ]);
})->with([
    ['nl', 'Deze gegevens komen niet overeen met onze records.'],
    ['en', 'These credentials do not match our records.'],
    ['fr', 'Ces identifiants ne correspondent pas à nos enregistrements.'],
]);
