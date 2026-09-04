<?php

use App\Models\Product;
use Illuminate\Support\Facades\Artisan;

test('the product page receives the edition figures from inventory', function () {
    Artisan::call('app:import-static-content-command');

    $this->get(localized('maison.products.show', ['product' => Product::FOUNDING_SLUG]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/products/show')
            ->where('productEdition.reserved', 0)
            ->where('productEdition.total', 100)
            ->where('productEdition.available', 99)
        );
});

test('the product stock line reads available, not a hardcoded remainder', function () {
    $source = file_get_contents(resource_path('js/components/maison/product/product-detail.tsx'));

    expect($source)
        ->toContain('edition.available')
        ->not->toContain("'27 nummers")
        ->not->toContain('"27 nummers');
});

test('the gallery is sticky with thumbnail tabs and cursor zoom', function () {
    $source = file_get_contents(resource_path('js/components/maison/product/product-gallery.tsx'));

    expect($source)
        ->toContain('lg:sticky')
        ->toContain('role="tablist"')
        ->toContain('overflow-x-auto')
        ->toContain('scrollbar-none')
        ->toContain("addEventListener('wheel'")
        ->toContain('cursor-zoom-in')
        ->toContain('scale(2.2)')
        ->toContain('prefers-reduced-motion');
});

test('reserve and newsletter actions use shell hooks rather than onclick strings', function () {
    $source = file_get_contents(resource_path('js/components/maison/product/product-detail.tsx'));

    expect($source)
        ->toContain('openPurchase')
        ->toContain('openNewsletter')
        ->not->toContain('onclick=');
});

test('the FAQ uses the accessible maison accordion', function () {
    $source = file_get_contents(resource_path('js/components/maison/product/product-faq.tsx'));

    expect($source)
        ->toContain('MaisonAccordion')
        ->not->toContain('faq-toggle');
});

test('the related house card is a crawlable maison link', function () {
    $source = file_get_contents(resource_path('js/components/maison/product/product-related.tsx'));

    expect($source)
        ->toContain('MaisonLink')
        ->toContain("to: 'house'")
        ->not->toContain('onclick=');
});

test('related product cards truncate long product titles', function () {
    $source = file_get_contents(resource_path('js/components/maison/product/product-related.tsx'));

    expect($source)
        ->toContain('RELATED_PRODUCT_TITLE_MAX = 40')
        ->toContain('truncateWithEllipsis(item.name, RELATED_PRODUCT_TITLE_MAX)');
});

test('the craft section stays within the mobile viewport for long locale copy', function () {
    $source = file_get_contents(resource_path('js/components/maison/product/product-craft.tsx'));

    expect($source)
        ->toContain('overflow-x-clip')
        ->toContain('min-w-0')
        ->toContain('wrap-break-word')
        ->toContain('md:grid-cols-2')
        ->not->toContain('sm:grid-cols-2');
});
