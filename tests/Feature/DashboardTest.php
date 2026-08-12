<?php

use App\Models\User;

test('guests are redirected to the localized home page', function () {
    $response = $this->get(localized('admin.dashboard'));
    $response->assertRedirect(localized('maison.home', absolute: false));
});

test('authenticated users can visit the admin dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(localized('admin.dashboard'));
    $response->assertOk();
});

test('legacy dashboard path redirects to the admin dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $this->get('/'.defaultLocale().'/dashboard')
        ->assertRedirect('/'.defaultLocale().'/admin/dashboard');
});
