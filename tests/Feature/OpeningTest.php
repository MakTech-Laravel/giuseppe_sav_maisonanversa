<?php

use Illuminate\Support\Facades\File;

/** The opening's folder, read as source text, as the rest of the home-page tests do. */
function openingPath(string $file = ''): string
{
    return resource_path('js/components/maison/opening/'.$file);
}

function openingSource(string $file): string
{
    return File::get(openingPath($file));
}

test('the opening replaces the photo slideshow in the shell', function () {
    expect(File::get(resource_path('js/layouts/frontend-layout.tsx')))
        ->toContain('<Opening />')
        ->not->toContain('ImmersiveIntro');

    expect(resource_path('js/components/maison/intro/immersive-intro.tsx'))->not->toBeFile()
        ->and(resource_path('js/lib/maison-intro.ts'))->not->toBeFile();
});

test('the lit house has eight rooms, in window order I to VIII, with nl, en and fr text', function () {
    $source = openingSource('opening-data.ts');

    preg_match_all("/numeral: '([IVX]+)'/", $source, $numerals);

    expect($numerals[1])->toBe(['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII']);

    foreach (['nl', 'en', 'fr'] as $locale) {
        // Each room carries one text block per locale, plus the copy block.
        expect(preg_match_all("/\b{$locale}: \{/", $source))->toBe(9);
    }
});

test('every room opens an existing page', function () {
    $source = openingSource('opening-data.ts');

    preg_match_all("/destination: \{ page: '(\w+)' \}/", $source, $pages);

    expect($pages[1])->toBe(['home', 'contact', 'circle', 'community', 'journal', 'story', 'corner'])
        ->and($source)->toContain("destination: { product: 'founding' }");

    foreach ($pages[1] as $page) {
        expect(config('maison.pages'))->toHaveKey($page);
    }
});

test('a room photo exists only as avif and webp, and is never a png', function () {
    $source = openingSource('opening-data.ts');

    preg_match_all("/photo: '(room-[a-z]+)'/", $source, $photos);

    expect($photos[1])->not->toBeEmpty();

    foreach ($photos[1] as $photo) {
        expect(public_path("images/optimized/rooms/{$photo}-768.avif"))->toBeFile()
            ->and(public_path("images/optimized/rooms/{$photo}-768.webp"))->toBeFile();
    }

    foreach (['opening-data.ts', 'opening.tsx', 'opening-scene.tsx', 'opening-rooms.tsx', 'opening-register.tsx'] as $file) {
        expect(openingSource($file))->not->toMatch('/\.png|images\/rooms\//');
    }
});

test('the house is inline svg, with no image requests until a room is chosen', function () {
    foreach (['door-house.html', 'wordmark.html'] as $art) {
        expect(File::get(openingPath("art/{$art}")))
            ->not->toContain('<image')
            ->not->toContain('<img')
            ->not->toContain('href="http');
    }

    // The only <img> lives in the room card, which exists only once a window is chosen.
    expect(openingSource('opening-scene.tsx'))->not->toContain('<img')
        ->and(openingSource('opening.tsx'))->not->toContain('<img')
        ->and(openingSource('opening-register.tsx'))->not->toContain('<img');

    expect(openingSource('opening-rooms.tsx'))->toContain('export function RoomCard');
});

test('it is one scene: the windows are the room buttons on the animated house, with no second screen', function () {
    $scene = openingSource('opening-scene.tsx');

    // Eight highlight overlays live in the artwork, one transparent button per window over it.
    expect(substr_count(File::get(openingPath('art/door-house.html')), 'class="hw-sel"'))->toBe(8);

    expect($scene)
        ->toContain('className="hw-btn"')
        ->toContain('aria-pressed={selected === index}')
        ->toContain('<RoomList')
        ->toContain('<RoomCard');

    // The wordmark header sits above the columns, so choosing a room never changes it:
    // only the right-hand column swaps between the list and a room card.
    expect(strpos($scene, 'className="op-identity"'))->toBeLessThan(strpos($scene, 'className="op-cols"'))
        ->and(strpos($scene, 'className="op-side"'))->toBeGreaterThan(strpos($scene, 'className="op-cols"'))
        ->and(strpos($scene, 'wordmarkArt }}'))->toBeLessThan(strpos($scene, 'className="op-side"'));

    expect(openingPath('opening-door.tsx'))->not->toBeFile()
        ->and(openingPath('opening-house.tsx'))->not->toBeFile()
        ->and(openingPath('art/lit-house.html'))->not->toBeFile();

    expect(openingSource('opening.tsx'))->not->toContain('Stage')->not->toContain("'house'");
});

test('the register has two buttons: one starts the real reservation, one goes to the home page', function () {
    $register = openingSource('opening-register.tsx');

    // Reserve: the site's own flow (login, the live edition picker, the order modal) ...
    expect($register)
        ->toContain('openPurchase')
        ->toContain('usePurchasesPaused')
        ->toContain('onClick={reserve}')
        // ... for the product the admin chose, after leaving the opening (the modals sit below it).
        ->toMatch('/onLeave\(\);[\s\S]*openPurchase\(\s*register\.product,/')
        // ... handing over the number the visitor already chose, so the picker does not ask again.
        ->toContain('editionPieceId: tile.id')
        // Home page: leaves the opening for the page behind it.
        ->toContain('onClick={onLeave}')
        ->toContain('copy.enterHouse')
        ->not->toContain('founding-circle/register');

    expect(File::get(resource_path('css/opening.css')))->toMatch('/\.ma-opening\s*\{[^}]*z-index:\s*9999/');
    expect(File::get(resource_path('js/components/maison/modals/maison-modal.tsx')))->toContain('z-9990');

    expect(openingSource('opening-data.ts'))->toContain("enterHouse: 'Enter the house'");
});

test('the register prints the real edition numbers of the chosen product, never a fixed 100', function () {
    $register = openingSource('opening-register.tsx');
    $data = openingSource('opening-data.ts');

    expect($register)
        ->toContain('register.tiles.map')
        ->toContain('disabled={!tile.available}')
        ->toContain('{tile.label}');

    // Nothing hard-coded is left: no fixed size, no placeholder reserved numbers.
    foreach (['OPENING_EDITION_SIZE', 'OPENING_RESERVED_NUMBERS', 'padNumber'] as $gone) {
        expect($data)->not->toContain($gone)
            ->and($register)->not->toContain($gone);
    }

    // The header's product and counts come from the register, and hide with it.
    expect(openingSource('opening-scene.tsx'))
        ->toContain('useOpeningRegister()')
        ->toContain('register.name')
        ->toContain('total: register.total')
        ->toContain('register !== null')
        ->not->toContain('Heritage Nº 001');

    expect($data)->toContain('{total}')->not->toContain('rackets');
});

test('the opening has a skip, a language switcher, and never leaves on its own', function () {
    $source = openingSource('opening.tsx');

    expect($source)
        ->toContain("t('Overslaan →')")
        ->toContain('switchLocale')
        ->toContain("event.key === 'Escape'")
        ->not->toContain('setInterval')
        ->not->toContain('IDLE');

    // The one timer is the fade after the visitor has chosen to leave.
    expect(substr_count($source, 'window.setTimeout('))->toBe(1);

    foreach (['en', 'fr'] as $locale) {
        expect(json_decode(File::get(base_path("lang/{$locale}.json")), true))->toHaveKey('Overslaan →');
    }
});

test('the opening plays once per session and decides before the first paint', function () {
    $source = openingSource('opening.tsx');

    // The first render matches the server HTML; the session check runs in a layout effect.
    expect($source)
        ->toContain('useState(true)')
        ->toContain('useLayoutEffect')
        ->toContain('shouldRun()')
        ->toContain("has('intro')")
        ->toContain("matchMedia(\n            '(prefers-reduced-motion: reduce)'");
});

test('the right light has four moods, driven by css variables', function () {
    $css = File::get(resource_path('css/opening.css'));

    foreach (['morning', 'afternoon', 'evening', 'night'] as $mood) {
        expect($css)->toContain("data-light='{$mood}'");
    }

    expect($css)
        ->toContain("html[data-opening='seen'] .ma-opening")
        ->toContain('prefers-reduced-motion: reduce');

    expect(File::get(resource_path('css/app.css')))->toContain("@import './opening.css'");
});

test('the windows light up in order after the door opens, then the list appears', function () {
    $css = File::get(resource_path('css/opening.css'));

    expect($css)
        ->toMatch('/\.mh-w0\s*\{\s*animation-delay:\s*2\.9s;/')
        ->toMatch('/\.mh-w7\s*\{\s*animation-delay:\s*4\.44s;/')
        ->toMatch('/\.mh-door\s*\{[^}]*2\.25s/');

    // Every window stays lit, since every window is a room button.
    expect($css)->toContain("[class^='lw']");

    // Drawn in the centre, then the house slides to its place and the rooms appear beside it.
    expect($css)
        ->toMatch('/\.op-house\s*\{[^}]*op-slide[^}]*4\.9s/')
        ->toMatch('/\.op-rooms\.op-in\s*\{\s*animation-delay:\s*6\.1s;/');
});
