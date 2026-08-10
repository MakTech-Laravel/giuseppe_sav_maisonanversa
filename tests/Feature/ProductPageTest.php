<?php

test('the product page receives the edition figures from config', function () {
    config([
        'maison.edition.reserved' => 73,
        'maison.edition.total' => 100,
    ]);

    $this->get('/nl/product')->assertOk()->assertInertia(fn ($page) => $page
        ->component('maison/product')
        ->where('edition.reserved', 73)
        ->where('edition.total', 100)
        ->where('edition.available', 27)
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
        ->toContain('cursor-zoom-in')
        ->toContain('scale(2.2)')
        ->toContain('prefers-reduced-motion');
});

test('reserve and newsletter actions use shell hooks rather than onclick strings', function () {
    $source = file_get_contents(resource_path('js/components/maison/product/product-detail.tsx'));

    expect($source)
        ->toContain('openOrder')
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
