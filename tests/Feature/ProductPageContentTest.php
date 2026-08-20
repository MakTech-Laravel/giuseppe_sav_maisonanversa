<?php

use App\Models\Product;
use Illuminate\Support\Facades\Artisan;

test('product page uses dynamic content and faq props', function () {
    Artisan::call('app:import-static-content-command');

    $this->get(localized('maison.product'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.slug', Product::FOUNDING_SLUG)
            ->has('product.gallery', 4)
            ->has('product.specs')
            ->has('related')
            ->has('faqs'));
});
