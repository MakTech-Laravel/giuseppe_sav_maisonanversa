<?php

use App\Models\User;

test('the site root redirects to the default locale without a preference', function () {
    $this->get('/')
        ->assertRedirect('/'.config('maison.default_locale'));
});

test('the site root redirects to the cookie locale preference', function () {
    $this->withUnencryptedCookie(config('maison.locale_cookie'), 'en')
        ->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get('/')
        ->assertRedirect('/en');
});

test('the site root prefers the cookie over Accept-Language', function () {
    $this->withUnencryptedCookie(config('maison.locale_cookie'), 'nl')
        ->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get('/')
        ->assertRedirect('/nl');
});

test('the site root uses Accept-Language when no preference is stored', function () {
    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9,en;q=0.8')
        ->get('/')
        ->assertRedirect('/fr');
});

test('visiting a localized page stores the locale preference cookie', function () {
    $this->get('/en')
        ->assertOk()
        ->assertPlainCookie(config('maison.locale_cookie'), 'en');
});

test('logout redirects to the preferred locale from the cookie', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withUnencryptedCookie(config('maison.locale_cookie'), 'en')
        ->post(route('logout'))
        ->assertRedirect(route('maison.home', ['locale' => 'en'], absolute: false));

    $this->assertGuest();
});
