<?php

use App\Models\User;

test('guests are redirected to the localized home page', function () {
    $response = $this->get(localized('dashboard'));
    $response->assertRedirect(localized('maison.home', absolute: false));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(localized('dashboard'));
    $response->assertOk();
});
