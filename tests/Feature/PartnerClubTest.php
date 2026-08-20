<?php

use Database\Seeders\PartnerClubSeeder;

test('corner page exposes dynamic clubs and form options', function () {
    $this->seed(PartnerClubSeeder::class);

    $this->get(localized('maison.corner'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs')
            ->has('cornerFormOptions.court_options')
            ->has('cornerFormOptions.format_options'));
});
