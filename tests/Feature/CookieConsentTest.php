<?php

test('visitors can store cookie consent', function () {
    $this->from('/nl')
        ->post(route('cookie-consent.store'), [
            'necessary' => 1,
            'analytics' => 1,
            'marketing' => 0,
        ])
        ->assertRedirect()
        ->assertCookie('maison_consent');
});

test('analytics script loads only with consent and a measurement id', function () {
    config(['services.analytics.measurement_id' => 'G-TEST123']);

    $this->withUnencryptedCookie('maison_consent', json_encode([
        'necessary' => true,
        'analytics' => true,
        'marketing' => false,
    ]))
        ->get('/nl')
        ->assertOk()
        ->assertSee('gtag/js?id=G-TEST123', false)
        ->assertSee('G-TEST123', false);
});

test('analytics script is omitted without consent', function () {
    config(['services.analytics.measurement_id' => 'G-TEST123']);

    $this->get('/nl')
        ->assertOk()
        ->assertDontSee('gtag/js?id=G-TEST123', false);
});

test('analytics script is omitted when measurement id is empty', function () {
    config(['services.analytics.measurement_id' => null]);

    $this->withUnencryptedCookie('maison_consent', json_encode([
        'necessary' => true,
        'analytics' => true,
        'marketing' => false,
    ]))
        ->get('/nl')
        ->assertOk()
        ->assertDontSee('googletagmanager.com/gtag', false);
});
