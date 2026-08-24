<?php

use App\Models\DressingItem;
use Database\Seeders\DressingItemSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('dressing page loads items from database', function () {
    $this->seed(DressingItemSeeder::class);

    $this->get(localized('maison.dressing'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('items')
            ->where('items.0.slug', 'padel-polo'));
});

test('dressing index exposes image, description-free card shape and status for each item', function () {
    $this->get(localized('maison.dressing'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/dressing')
            ->has('items.0.name')
            ->has('items.0.slug')
            ->has('items.0.category')
            ->has('items.0.status')
            ->has('items.0.image_url')
            ->has('items.0.image_key')
            ->where('items.0.status', fn ($status) => in_array($status, ['coming_soon', 'available'], true))
        );
});

test('published dressing item detail page renders with description and status', function () {
    $item = DressingItem::factory()->create([
        'name' => 'Show Detail Item',
        'description' => 'Uitgebreide beschrijving van dit stuk.',
        'status' => 'available',
        'is_published' => true,
    ]);

    $this->get(localized('maison.dressing.show', ['dressingItem' => $item->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/dressing-show')
            ->where('item.name', 'Show Detail Item')
            ->where('item.description', 'Uitgebreide beschrijving van dit stuk.')
            ->where('item.status', 'available')
        );
});

test('unpublished dressing item detail page returns 404', function () {
    $item = DressingItem::factory()->create([
        'is_published' => false,
    ]);

    $this->get(localized('maison.dressing.show', ['dressingItem' => $item->slug]))
        ->assertNotFound();
});
