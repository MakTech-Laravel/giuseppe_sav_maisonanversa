<?php

use App\Models\User;
use Inertia\Support\Header;

test('inertia login redirects to the member dashboard instead of returning json', function () {
    $user = User::factory()->create();

    $response = $this
        ->withHeaders([
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Requested-With' => 'XMLHttpRequest',
            Header::INERTIA => 'true',
            Header::VERSION => 'test',
        ])
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticated();
    $response->assertRedirect(localized('member.dashboard', absolute: false));
    expect($response->headers->get('content-type'))->not->toContain('application/json');
});

test('inertia login redirects even when the client prefers json', function () {
    $user = User::factory()->create();

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
            Header::INERTIA => 'true',
        ])
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticated();
    $response->assertRedirect(localized('member.dashboard', absolute: false));
});

test('non-inertia json login still returns the fortify payload', function () {
    $user = User::factory()->create();

    $response = $this
        ->withHeaders([
            'Accept' => 'application/json',
        ])
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticated();
    $response->assertOk()->assertJson(['two_factor' => false]);
});
