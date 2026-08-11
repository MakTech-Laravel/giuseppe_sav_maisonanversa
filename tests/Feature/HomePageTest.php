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

test('the antwerp section fills its portrait frame with the cityscape photograph', function () {
    expect(public_path('images/brand/antwerp-cityscape.png'))->toBeFile();

    $source = file_get_contents(resource_path('js/components/maison/home/home-antwerp.tsx'));

    expect($source)
        ->toContain('asset="antwerp-cityscape"')
        ->toContain('aspect-4/5')
        ->toContain('object-cover');

    expect(Imagery::existingPaths())
        ->toContain('images/brand/antwerp-cityscape.png');
});

test('the home product section keeps the three-column founding edition layout', function () {
    expect(public_path('images/product/heritage-001-front.png'))->toBeFile();

    $source = file_get_contents(resource_path('js/components/maison/home/home-product.tsx'));

    expect($source)
        ->toContain('ma-lg:grid-cols-[1fr_1.3fr_1fr]')
        ->toContain('asset="heritage-001-front"')
        ->toContain('aspect-3/4')
        ->toContain('001 / 100')
        ->toContain('Stuks Alleen')
        ->not->toContain('hidden ma-lg:block');

    expect(Imagery::existingPaths())
        ->toContain('images/product/heritage-001-front.png');
});

test('the content grid uses crawlable links rather than click handlers on divs', function () {
    $source = file_get_contents(resource_path('js/components/maison/home/home-content-grid.tsx'));

    expect($source)
        ->toContain('MaisonLink')
        ->not->toContain('onClick={() =>');
});

test('the content grid is one column on small, two on medium, and four on large', function () {
    expect(public_path('images/product/heritage-001-detail-gravure.png'))->toBeFile();

    $source = file_get_contents(resource_path('js/components/maison/home/home-content-grid.tsx'));

    expect($source)
        ->toContain('grid-cols-1')
        ->toContain('ma-md:grid-cols-2')
        ->toContain('ma-lg:grid-cols-4')
        ->toContain("'heritage-001-detail-gravure'")
        ->toContain('Club Corner');

    expect(Imagery::existingPaths())
        ->toContain('images/product/heritage-001-detail-gravure.png');
});

test('the home story section keeps a three-column layout with values always visible', function () {
    $source = file_get_contents(resource_path('js/components/maison/home/home-story.tsx'));

    expect($source)
        ->toContain('ma-lg:grid-cols-[1fr_1.1fr_1fr]')
        ->toContain('ma-md:grid-cols-2')
        ->toContain('Ons Verhaal')
        ->toContain('Europees Erfgoed')
        ->toContain('+')
        ->toContain('Maison Anversa')
        ->toContain('European Heritage Sports and Lifestyle House')
        ->toContain('Antwerp · Belgium · Est. 2026')
        ->toContain('bg-choc2')
        ->toContain('rounded-full border border-gold')
        ->not->toContain('hidden flex-col');
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
