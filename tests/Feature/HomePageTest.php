<?php

test('the home page receives the edition figures from config', function () {
    config([
        'maison.edition.reserved' => 73,
        'maison.edition.total' => 100,
    ]);

    $this->get('/nl')->assertOk()->assertInertia(fn ($page) => $page
        ->component('maison/home')
        ->where('edition.reserved', 73)
        ->where('edition.total', 100)
        ->where('edition.available', 27)
    );
});

test('available stock cannot go below zero when reserved exceeds total', function () {
    config([
        'maison.edition.reserved' => 120,
        'maison.edition.total' => 100,
    ]);

    $this->get('/nl')->assertInertia(fn ($page) => $page
        ->where('edition.available', 0)
    );
});

test('the home page wires the progress bar to the edition figures', function () {
    $source = file_get_contents(resource_path('js/components/maison/home/home-preorder.tsx'));

    expect($source)
        ->toContain('edition.reserved')
        ->toContain('edition.total')
        ->toContain('IntersectionObserver')
        ->toContain('role="progressbar"');
});

test('the hero stock counter reads available, not a hardcoded remainder', function () {
    $source = file_get_contents(resource_path('js/components/maison/home/home-hero.tsx'));

    expect($source)
        ->toContain('edition.available')
        ->not->toContain('>27<')
        ->not->toContain("'27'");
});

test('the content grid uses crawlable links rather than click handlers on divs', function () {
    $source = file_get_contents(resource_path('js/components/maison/home/home-content-grid.tsx'));

    expect($source)
        ->toContain('MaisonLink')
        ->not->toContain('onClick={() =>');
});
