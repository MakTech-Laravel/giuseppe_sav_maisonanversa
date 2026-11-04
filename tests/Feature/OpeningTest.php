<?php

use App\Models\OpeningOffer;
use App\Models\Product;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;

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

    // One column, top to bottom: the house, then the wordmark and edition, then the register
    // with its two buttons, then the list of rooms. A chosen room opens over all of it.
    expect(strpos($scene, 'className="op-house"'))->toBeLessThan(strpos($scene, 'className="op-identity"'))
        ->and(strpos($scene, 'className="op-identity"'))->toBeLessThan(strpos($scene, '<OpeningRegister'))
        ->and(strpos($scene, '<OpeningRegister'))->toBeLessThan(strpos($scene, '<RoomList'))
        ->and(strpos($scene, 'wordmarkArt }}'))->toBeGreaterThan(strpos($scene, 'doorHouseArt }}'))
        ->and($scene)->not->toContain('op-cols')->not->toContain('op-side');

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
        ->toContain('visibleTiles.map')
        ->toContain('disabled={!tile.available}')
        ->toContain('{tile.label}');

    // Nothing hard-coded is left: no fixed size, no placeholder reserved numbers.
    foreach (['OPENING_EDITION_SIZE', 'OPENING_RESERVED_NUMBERS', 'padNumber'] as $gone) {
        expect($data)->not->toContain($gone)
            ->and($register)->not->toContain($gone);
    }

    // The edition line is the chosen product's own name and the limit line's counts come from
    // the register; both hide with it.
    expect(openingSource('opening-scene.tsx'))
        ->toContain('useOpeningRegister()')
        ->toContain('{register.name}')
        ->toContain('total: register.total')
        ->toContain('register !== null')
        ->not->toContain('copy.edition');

    expect($data)->toContain('{total}');
});

test('the limit line reads as the client wrote it, in every language, and the edition is not hard-coded', function () {
    $data = openingSource('opening-data.ts');

    expect($data)
        ->not->toContain('OPENING_EDITION')
        ->not->toContain('Founding Edition')
        ->not->toMatch('/^\s*edition:/m')
        ->toContain("'Limited to {total} numbered rackets · {n} numbers available'")
        ->toContain("'Beperkt tot {total} genummerde rackets · nog {n} nummers vrij'")
        ->toContain("'Limitée à {total} raquettes numérotées · {n} numéros disponibles'")
        ->not->toContain('pieces')
        ->not->toContain('stuks');
});

test('the register grid is ten per row on desktop and tablet, five on a phone', function () {
    $css = File::get(resource_path('css/opening.css'));

    expect($css)
        ->toMatch('/\.op-numbers\s*\{[^}]*grid-template-columns:\s*repeat\(10,/')
        ->toMatch('/@media \(max-width: 639px\)\s*\{\s*\.op-numbers\s*\{\s*grid-template-columns:\s*repeat\(5,/')
        ->not->toContain('--op-tile')
        ->not->toContain('auto-fill');

    expect(openingSource('opening-register.tsx'))->not->toContain('--op-tile');
});

test('by day a window is a soft reflection that lights fully on hover, focus or tap', function () {
    $css = File::get(resource_path('css/opening.css'));
    $scene = openingSource('opening-scene.tsx');

    // About 25% at rest in the morning and afternoon; the evening follows the briefing.
    expect($css)
        ->toMatch('/\.ma-opening\s*\{[^}]*--lit:\s*0\.25;/')
        ->toMatch("/data-light='evening'\] \.ma-opening\s*\{[^}]*--lit:\s*0\.95;/")
        ->toMatch("/\.mh-house \[class\^='lw'\]\.is-on\s*\{\s*opacity:\s*1;/");

    // At night only the balcony window (IV) and one dormer (VII) still burn.
    expect($css)
        ->toMatch("/data-light='night'\] \.ma-opening\s*\{[^}]*--lit:\s*0;[^}]*--lit-key:\s*0\.9;/")
        ->toMatch('/\.mh-house \.lw3,\s*\.mh-house \.lw6\s*\{\s*opacity:\s*var\(--lit-key\);/');

    expect($scene)
        ->toContain('onPointerEnter')
        ->toContain('onFocus')
        ->toContain("classList.toggle(\n                    'is-on'");
});

test('a chosen room is full screen, with its name and the way in on top of the photo', function () {
    $css = File::get(resource_path('css/opening.css'));
    $rooms = openingSource('opening-rooms.tsx');

    expect($css)->toMatch('/\.op-room\s*\{[^}]*position:\s*fixed;[^}]*inset:\s*0;/')
        ->and($css)->toMatch('/\.op-photo\s*\{[^}]*position:\s*absolute;[^}]*inset:\s*0;/');

    // The photo, then the body that sits over it; the card is not rendered beside the house any more.
    expect(strpos($rooms, 'className="op-photo"'))->toBeLessThan(strpos($rooms, 'className="op-room-body"'))
        ->and($rooms)->toContain('role="dialog"')
        ->and(openingSource('opening-scene.tsx'))->toContain("event.key === 'Escape'");
});

test('the wordmark and edition fade in after the door has opened, not from the start', function () {
    $css = File::get(resource_path('css/opening.css'));

    // The door finishes opening at 2.25s + 1.15s; the wordmark starts as it does, the register and rooms follow.
    expect($css)
        ->toMatch('/\.op-t1\s*\{\s*animation-delay:\s*3s;/')
        ->toMatch('/\.op-t2\s*\{\s*animation-delay:\s*3\.35s;/')
        ->toMatch('/\.op-register\.op-in\s*\{\s*animation-delay:\s*3\.7s;/')
        ->toMatch('/\.op-rooms\.op-in\s*\{\s*animation-delay:\s*4\.4s;/');
});

test('the leaving class is joined with its space, so the overlay fades out in its own styles', function () {
    // A template string here loses its space to the Tailwind class sorter, which fused the two classes.
    expect(openingSource('opening.tsx'))
        ->toContain("cn('ma-opening', leaving && 'is-leaving')")
        ->not->toContain('`ma-opening');

    expect(openingSource('opening-rooms.tsx'))->toContain("cn('op-room', photo !== null && 'has-photo')");
});

test('the house is in the first html, with the edition under it, before any script runs', function () {
    $html = $this->get('/nl')->assertOk()->getContent();

    // The seeded opening product is the founding one, named as the client writes it.
    expect(ProductSeeder::FOUNDING_NAME)->toBe('Heritage Nº 001 · Founding Edition');

    expect($html)
        ->toContain('id="op-boot"')
        ->toContain('class="mh-house"')
        ->toContain('<p class="op-edition op-in op-t2">Heritage Nº 001 · Founding Edition</p>');

    // The house comes before the wordmark and the edition in the markup.
    expect(strpos($html, 'class="mh-house"'))->toBeLessThan(strpos($html, 'class="ma-wordmark'))
        ->and(strpos($html, 'class="ma-wordmark'))->toBeLessThan(strpos($html, 'class="op-edition'));

    // Other pages do not carry it.
    expect($this->get('/nl/contact')->getContent())->not->toContain('id="op-boot"');
});

test('the edition line under the house is the name of the product the admin chose', function () {
    OpeningOffer::query()->delete();

    $product = Product::factory()->limitedEdition(12)->create(['name' => 'Heritage Nº 002 · Second Edition']);
    OpeningOffer::factory()->create(['product_id' => $product->id]);

    $html = $this->get('/nl')->assertOk()->getContent();

    expect($html)
        ->toContain('<p class="op-edition op-in op-t2">Heritage Nº 002 · Second Edition</p>')
        ->not->toContain('<p class="op-edition op-in op-t2">Heritage Nº 001');

    // The page prop React reads is the same name.
    $this->get('/nl')->assertInertia(fn ($page) => $page->where('opening.name', 'Heritage Nº 002 · Second Edition'));
});

test('with no active opening offer there is no edition line', function () {
    OpeningOffer::query()->delete();

    $this->get('/nl')->assertOk()->assertInertia(fn ($page) => $page->where('opening', null));

    expect($this->get('/nl')->getContent())
        ->toContain('id="op-boot"')
        ->not->toContain('class="op-edition');
});

test('the register is paged only above 100 numbers: 100 or fewer shows no pager', function () {
    $register = openingSource('opening-register.tsx');
    $data = openingSource('opening-data.ts');
    $css = File::get(resource_path('css/opening.css'));

    expect($data)->toContain('export const OPENING_PAGE_SIZE = 100;');

    expect($register)
        // More than one page's worth, and only then: `>`, not `>=`.
        ->toContain('register.tiles.length > OPENING_PAGE_SIZE')
        ->not->toContain('>= OPENING_PAGE_SIZE')
        ->toContain('{paged ? (')
        ->toContain('<nav className="op-pager" aria-label={copy.pagination}>')
        ->toMatch("/aria-current=\{\s*item === currentPage \? 'page' : undefined\s*\}/")
        // Every tile stays client-side, so a chosen number survives a page change and reaches the picker.
        ->toContain('register.tiles.find((entry) => entry.id === selected)')
        ->toContain('register.tiles.slice(')
        // An edition that shrinks never leaves the visitor on an empty page.
        ->toContain('Math.min(page, Math.max(pages - 1, 0))');

    // Built from the register's own look, in the page's colours, with a visible focus ring.
    expect($css)
        ->toContain('.op-pager {')
        ->toMatch("/\.op-page\[aria-current='page'\]\s*\{[^}]*var\(--selbg\)/")
        ->toMatch('/\.op-page:focus-visible,/');

    // Labels in all three languages.
    foreach (['pagination', 'previousPage', 'nextPage', 'pageLabel', 'numbersRange'] as $key) {
        expect(substr_count($data, "        {$key}: "))->toBe(3);
    }
});

test('no page asks the browser for a room png: the share image is a small jpg', function () {
    $html = $this->get('/nl')->assertOk()->getContent();

    // Tags the browser or a crawler would fetch: link, meta and src, not the inertia page data.
    preg_match_all('/<(?:link|meta|img|source)\b[^>]*>/i', $html, $tags);

    expect(implode("\n", $tags[0]))->not->toMatch('/images\/rooms\/[^"\s]*\.png/')
        ->and(config('maison.seo.image'))->toEndWith('.jpg')
        ->and(config('maison.seo.image_width'))->toBe(1200)
        ->and(config('maison.seo.image_height'))->toBe(630);

    $share = public_path(config('maison.seo.image'));

    expect($share)->toBeFile()
        ->and(filesize($share))->toBeLessThan(150 * 1024)
        ->and(array_slice(getimagesize($share), 0, 2))->toBe([1200, 630]);
});

test('exactly two fonts are preloaded: the latin Baskervville 400 and Montserrat 300', function () {
    // The test case stubs Vite out; use the real one, as a build does. A running dev
    // server (public/hot) serves the fonts itself, so point it away from that file.
    $this->withVite();
    Vite::useHotFile(storage_path('framework/testing/no-hot'));

    $html = $this->get('/nl')->assertOk()->getContent();

    preg_match_all('/<link[^>]+rel="preload"[^>]+as="font"[^>]*>/i', $html, $links);

    expect($links[0])->toHaveCount(2)
        ->and($links[0][0])->toMatch('/baskervville-400-normal-[\w-]+\.woff2/')
        ->and($links[0][1])->toMatch('/montserrat-300-normal-[\w-]+\.woff2/');

    expect(File::get(base_path('vite.config.ts')))->not->toContain('preload: [');
})->skip(
    fn () => ! File::exists(public_path('build/manifest.json')) || ! File::exists(public_path('build/fonts-manifest.json')),
    'needs a production build (npm run build)',
);

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

    // The house stays where it is drawn; the list of rooms comes in under the register.
    expect($css)
        ->not->toContain('op-slide')
        ->toMatch('/\.op-rooms\.op-in\s*\{\s*animation-delay:\s*4\.4s;/');
});
