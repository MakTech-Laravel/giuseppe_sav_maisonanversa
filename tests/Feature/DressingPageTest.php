<?php

use Database\Seeders\DressingItemSeeder;

test('dressing page loads items from database', function () {
    $this->seed(DressingItemSeeder::class);

    $this->get(localized('maison.dressing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('items')
            ->where('items.0.slug', 'padel-polo'));
});
