<?php

test('visitors can store cookie consent', function () {
    $this->from('/nl')
        ->post(route('cookie-consent.store'), [
            'necessary' => 1,
            'analytics' => 1,
        ])
        ->assertRedirect()
        ->assertCookie('maison_consent');
});

test('cookie consent ignores a marketing field and does not persist it', function () {
    $response = $this->from('/nl')
        ->post(route('cookie-consent.store'), [
            'necessary' => 1,
            'analytics' => 0,
            'marketing' => 1,
        ])
        ->assertRedirect()
        ->assertCookie('maison_consent');

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($item) => $item->getName() === 'maison_consent');

    expect($cookie)->not->toBeNull();

    $payload = json_decode($cookie->getValue(), true);

    expect($payload)
        ->toMatchArray([
            'necessary' => true,
            'analytics' => false,
        ])
        ->not->toHaveKey('marketing');
});

test('analytics script loads only with consent and a measurement id', function () {
    config(['services.analytics.measurement_id' => 'G-TEST123']);

    $this->withUnencryptedCookie('maison_consent', json_encode([
        'necessary' => true,
        'analytics' => true,
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
    ]))
        ->get('/nl')
        ->assertOk()
        ->assertDontSee('googletagmanager.com/gtag', false);
});

test('the cookie banner has no marketing preference', function () {
    $source = file_get_contents(resource_path('js/components/maison/cookie-consent-banner.tsx'));

    expect($source)
        ->toContain('Analytics')
        ->toContain('Necessary')
        ->toContain('Analytics load only after your consent')
        ->toContain('COOKIE_SETTINGS_EVENT')
        ->not->toContain('marketing')
        ->not->toContain('Marketing');
});
