<?php

use App\Models\SeoMeta;
use Database\Seeders\SeoMetaSeeder;

test('seo copy is loaded from seo meta cms with fallback support', function () {
    $this->seed(SeoMetaSeeder::class);

    SeoMeta::query()->where('page_key', 'product')->update([
        'title' => 'Custom Product Title',
        'description' => 'Custom Product Description',
    ]);

    $this->get(localized('maison.product'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('seo.title', 'Custom Product Title')
            ->where('seo.description', 'Custom Product Description'));
});
