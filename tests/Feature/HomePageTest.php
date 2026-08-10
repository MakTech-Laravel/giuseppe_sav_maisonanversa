<?php

use App\Support\Imagery;

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

test('the home marquee sits on chocolate with cream type', function () {
    $source = file_get_contents(resource_path('js/components/maison/home/home-marquee.tsx'));

    expect($source)
        ->toContain('bg-choc')
        ->toContain('text-cream/85')
        ->toContain('border-gold/20')
        ->toContain('Gebouwd voor generaties')
        ->not->toContain('bg-cream')
        ->not->toContain('bg-gold/8')
        ->not->toContain('mask-image');
});

test('the home hero uses the mansion photograph as its full-bleed backdrop', function () {
    $source = file_get_contents(resource_path('js/components/maison/home/home-hero.tsx'));

    expect($source)
        ->toContain('asset="hero-mansion"')
        ->toContain('className="absolute inset-0 h-full w-full"')
        ->not->toContain('-z-10');

    expect(Imagery::existingPaths())
        ->toContain('images/brand/hero-mansion.png');
});

test('the mansion photograph is on disk under the public brand path', function () {
    expect(public_path('images/brand/hero-mansion.png'))->toBeFile();
});

test('the content grid uses crawlable links rather than click handlers on divs', function () {
    $source = file_get_contents(resource_path('js/components/maison/home/home-content-grid.tsx'));

    expect($source)
        ->toContain('MaisonLink')
        ->not->toContain('onClick={() =>');
});

test('the document paints a boot cover before React so the home page cannot flash under the loader', function () {
    $this->get('/nl')
        ->assertOk()
        ->assertSee('maison-boot-cover', false)
        ->assertSee('maison.intro.seen', false);
});

test('the intro holds room photography back until the preloader curtain has lifted', function () {
    $source = file_get_contents(resource_path('js/components/maison/intro/immersive-intro.tsx'));

    expect($source)
        ->toContain('panel={curtainLifted ? introPanel(slide) : -1}')
        ->not->toContain('panel={loaderDone ? introPanel(slide) : -1}');
});
