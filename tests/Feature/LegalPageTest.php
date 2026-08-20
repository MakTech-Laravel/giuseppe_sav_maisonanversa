<?php

use Database\Seeders\LegalPageSeeder;

test('legal pages are served from cms records', function () {
    $this->seed(LegalPageSeeder::class);

    $this->get(localized('maison.privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('legalPage.slug', 'privacy')
            ->has('legalPage.body'));
});
