<?php

test('html responses send a content security policy with a vite nonce', function () {
    $response = $this->get('/nl');

    $response->assertOk();

    $csp = (string) $response->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("script-src 'self' 'nonce-")
        ->toContain('https://js.stripe.com')
        ->toContain('https://checkout.stripe.com')
        ->toContain('https://fonts.bunny.net')
        ->toContain('https://www.googletagmanager.com')
        ->toContain('http://127.0.0.1:5173')
        ->toContain("style-src 'self' 'unsafe-inline' https://fonts.bunny.net http://localhost:5173 http://127.0.0.1:5173")
        ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
});

test('non-production responses are tagged noindex', function () {
    $response = $this->get('/nl');

    $response->assertOk();

    expect($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
});

test('production responses are not tagged noindex', function () {
    $this->app['env'] = 'production';

    $response = $this->get('/nl');

    $response->assertOk();

    expect($response->headers->get('X-Robots-Tag'))->toBeNull();
});

test('https responses send hsts', function () {
    $response = $this->get('https://localhost/nl');

    $response->assertOk();

    expect($response->headers->get('Strict-Transport-Security'))
        ->toBe('max-age=31536000; includeSubDomains');
});

test('the boot cover script carries the csp nonce', function () {
    $response = $this->get('/nl')->assertOk();

    $response->assertSee('nonce=', false);
    $response->assertSee("cover.id = 'maison-boot-cover'", false);
});
