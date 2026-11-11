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

    // House names stay the same in every language; only the small label is translated.
    foreach (['Salon', 'Bureau', 'Le Cercle', 'Galerie', 'Salle Commune', 'Bibliothèque', 'Atelier', 'Club Corner'] as $name) {
        expect(substr_count($source, "name: '{$name}'"))->toBe(3);
    }

    foreach (['In jouw club', 'In your club', 'Dans votre club'] as $label) {
        expect(substr_count($source, "subtitle: '{$label}'"))->toBe(1);
    }

    // A window and a name go straight to the room's page: no room view, so no "Step inside" button.
    expect($source)
        ->not->toContain('enter:')
        ->not->toContain('Step inside')
        ->not->toContain('line:')
        ->not->toContain("subtitle: 'The Playroom'")
        ->not->toContain('speelkamer')
        ->not->toContain('salle de jeux')
        ->not->toContain("name: 'Office'")
        ->not->toContain("name: 'Gallery'")
        ->not->toContain("name: 'Library'");
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

test('the opening has no room photos and no room view, and requests no room image', function () {
    foreach (['opening-data.ts', 'opening.tsx', 'opening-scene.tsx', 'opening-rooms.tsx', 'opening-register.tsx'] as $file) {
        expect(openingSource($file))
            ->not->toMatch('/\.png|\.avif|\.webp|images\/(optimized\/)?rooms\//')
            ->not->toContain('photo:')
            ->not->toContain('openingPhotoSources')
            ->not->toContain('RoomCard')
            ->not->toContain('<picture')
            ->not->toContain('<img');
    }

    $css = File::get(resource_path('css/opening.css'));

    expect($css)
        ->not->toMatch('/\.op-room(?!s)/')
        ->not->toContain('.op-photo')
        ->not->toContain('hw-sel');

    // The photos stay on disk: the Journal and the imagery helper still use them.
    expect(public_path('images/optimized/rooms/room-library-768.avif'))->toBeFile();
});

test('the house is inline svg, with no image requests until a room is chosen', function () {
    foreach (['door-house.html', 'wordmark.html'] as $art) {
        expect(File::get(openingPath("art/{$art}")))
            ->not->toContain('<image')
            ->not->toContain('<img')
            ->not->toContain('href="http');
    }

    // No <img> anywhere in the opening: the room view with photos is gone.
    foreach (['opening-scene.tsx', 'opening.tsx', 'opening-register.tsx', 'opening-rooms.tsx'] as $file) {
        expect(openingSource($file))->not->toContain('<img');
    }
});

test('it is one scene: the windows are the room links on the animated house, with no second screen', function () {
    $scene = openingSource('opening-scene.tsx');
    $rooms = openingSource('opening-rooms.tsx');

    // The artwork carries the eight lit windows only; selecting a window no longer exists.
    $art = File::get(openingPath('art/door-house.html'));

    expect(substr_count($art, '<g class="lw'))->toBe(8)
        ->and($art)->not->toContain('hw-sel');

    // One transparent link per window over the artwork, and one link per name in the list.
    expect($scene)
        ->toContain('<RoomWindow')
        ->toContain('<RoomList')
        ->not->toContain('selected')
        ->not->toContain('<RoomCard');

    expect($rooms)
        ->toContain("cn('hw-btn'")
        ->toContain('<MaisonLink')
        ->toContain('<ol className="op-rl">');

    // One column, top to bottom: the house, then the wordmark and edition, then the register
    // with its two buttons, then the list of rooms.
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
    $css = File::get(resource_path('css/opening.css'));

    expect($register)
        ->toContain('visibleTiles.map')
        ->toContain('disabled={!tile.available}')
        ->toContain('{tile.label}')
        ->toContain("tile.status === 'archive'")
        ->toContain('op-num-archive')
        ->toContain('copy.archive');

    expect($data)->toContain("archive: 'Maison Archive'")
        ->and($css)->toContain('.op-num-archive')
        ->and($css)->toContain('.op-sw-archive');

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
        ->toContain("'Édition limitée à {total} raquettes numérotées · encore {n} numéros disponibles'")
        ->not->toContain('pieces')
        ->not->toContain('stuks');
});

test('the register is one centred column of 600 px, ten per row on every screen', function () {
    $css = File::get(resource_path('css/opening.css'));

    // The register and the room list share the same 600 px column, centred.
    expect($css)
        ->toMatch('/\.op-register\s*\{[^}]*max-width:\s*600px;[^}]*margin:\s*0 auto;/')
        ->toMatch('/\.op-rooms\s*\{[^}]*max-width:\s*600px;[^}]*margin:\s*0 auto;/')
        // Title and legend are centred, not split left and right.
        ->toMatch('/\.op-reg-head\s*\{[^}]*text-align:\s*center;/')
        ->toMatch('/\.op-legend\s*\{[^}]*justify-content:\s*center;/')
        ->not->toContain('justify-content: space-between;
    align-items: baseline;');

    // 10 per row, gap 6 px, tiles 5:4 and at least 44 px high.
    expect($css)
        ->toMatch('/\.op-numbers\s*\{[^}]*grid-template-columns:\s*repeat\(10,[^}]*gap:\s*6px;/')
        ->toMatch('/\.op-num\s*\{[^}]*aspect-ratio:\s*5 \/ 4;[^}]*min-height:\s*44px;/')
        ->not->toContain('--op-tile')
        ->not->toContain('auto-fill');

    // On a phone it stays 10 x 10, compact: gap 3 px, tiles 34 px high, numbers 10.5 px.
    expect($css)
        ->toMatch('/@media \(max-width: 560px\)\s*\{[\s\S]*?\.op-numbers\s*\{\s*gap:\s*3px;/')
        ->toMatch('/@media \(max-width: 560px\)\s*\{[\s\S]*?\.op-num\s*\{[^}]*height:\s*34px;[^}]*font-size:\s*10\.5px;/')
        ->toMatch('/@media \(max-width: 560px\)\s*\{[\s\S]*?\.op-rl-lb\s*\{\s*display:\s*none;/')
        ->not->toMatch('/repeat\(5,/');

    // Maison Archive: same dashed taupe tile and the same legend swatch, not selectable.
    expect($css)
        ->toMatch('/\.op-num\.op-num-archive:disabled\s*\{[^}]*#ede3d8;[^}]*1px dashed #8d705a;[^}]*#6e5644;/')
        ->toMatch('/\.op-sw-archive\s*\{[^}]*1px dashed #8d705a;[^}]*#ede3d8;/');

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

    // Hover and keyboard focus (in the house or through the list) light a window in 0.3 s.
    expect($css)->toMatch("/\.mh-house \[class\^='lw'\]\s*\{[^}]*transition:\s*opacity 0\.3s/");

    expect($scene)->toContain("classList.toggle('is-on', index === hovered)")
        ->and(openingSource('opening-rooms.tsx'))
        ->toContain('onPointerEnter')
        ->toContain('onFocus')
        ->toContain('onBlur');
});

test('a window and a room name are links straight to the room page, with a label on hover and focus', function () {
    $css = File::get(resource_path('css/opening.css'));
    $rooms = openingSource('opening-rooms.tsx');
    $data = openingSource('opening-data.ts');

    // Both are real links, to the same URL, and both leave the opening.
    expect(substr_count($rooms, 'href={roomHref(room, locale)}'))->toBe(2)
        ->and(substr_count($rooms, 'onClick={onLeave}'))->toBe(2)
        ->and($rooms)->toContain('aria-label={`${text.name}, ${text.subtitle}`}')
        ->toContain('{text.name} · {text.subtitle}')
        ->not->toContain('role="dialog"');

    // The label shows on hover, on keyboard focus and when the list row is hovered.
    expect($css)
        ->toMatch('/\.hw-btn:hover \.op-tip,\s*\.hw-btn:focus-visible \.op-tip,\s*\.hw-btn\.hov \.op-tip\s*\{\s*opacity:\s*1;/')
        ->toMatch('/\.op-rl a:hover,\s*\.op-rl a\.hov\s*\{/')
        ->toMatch('/\.hw-btn:focus-visible,[\s\S]*?\{\s*outline:\s*2px solid #8d705a;/');

    // The windows sit where the reference puts them.
    expect($data)
        ->toContain('left: 15.71, top: 66.709, width: 12.84, height: 22.864')
        ->toContain('left: 17.976, top: 17.211, width: 8.157, height: 11.683')
        ->toContain('left: 74.169, top: 17.211, width: 8.308, height: 11.683');

    // Escape no longer has a room view to close: it leaves the opening, as before.
    expect(openingSource('opening-scene.tsx'))->not->toContain("event.key === 'Escape'")
        ->and(openingSource('opening.tsx'))->toContain("event.key === 'Escape'");
});

test('every room link goes to the page named in the design update', function () {
    $routes = [
        'home' => '/{lang}',
        'contact' => '/{lang}/contact',
        'circle' => '/{lang}/circle',
        'community' => '/{lang}/community',
        'journal' => '/{lang}/journal',
        'story' => '/{lang}/story',
        'corner' => '/{lang}/corner',
    ];

    foreach ($routes as $page => $path) {
        foreach (['nl', 'en', 'fr'] as $locale) {
            $uri = trim(config("maison.pages.{$page}"), '/');
            $expected = str_replace('{lang}', $locale, $path);

            expect(rtrim("/{$locale}/{$uri}", '/'))->toBe($expected);
        }
    }

    // Galerie: the founding product.
    expect(openingSource('opening-data.ts'))->toContain("destination: { product: 'founding' }")
        ->and(File::get(resource_path('js/lib/maison-navigation.ts')))->toContain("FOUNDING_PRODUCT_SLUG = 'heritage-no-001'");
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
        ->toContain('copy.skip')
        ->not->toContain("t('Overslaan")
        ->toContain('switchLocale')
        ->toContain("event.key === 'Escape'")
        ->not->toContain('setInterval')
        ->not->toContain('IDLE');

    // The one timer is the fade after the visitor has chosen to leave.
    expect(substr_count($source, 'window.setTimeout('))->toBe(1);

    $data = openingSource('opening-data.ts');

    foreach (["skip: 'Overslaan'", "skip: 'Skip'", "skip: 'Passer'"] as $skip) {
        expect($data)->toContain($skip);
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

    // Mood follows Antwerp time; ?light= still overrides for testing.
    $blade = File::get(resource_path('views/app.blade.php'));

    expect($blade)
        ->toContain('Europe/Brussels')
        ->toContain('light=(morning|afternoon|evening|night)')
        ->not->toContain('new Date().getHours()');
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

test('the final greetings follow Antwerp time, and at night use the evening greeting', function () {
    $data = openingSource('opening-data.ts');

    expect($data)
        ->toContain("morning: 'Goedemorgen uit Antwerpen'")
        ->toContain("afternoon: 'Goedemiddag uit Antwerpen'")
        ->toContain("night: 'Goedenavond uit Antwerpen'")
        ->toContain("night: 'Good evening from Antwerp'")
        ->toContain("morning: 'Bonjour d’Anvers'")
        ->toContain("afternoon: 'Bonjour d’Anvers'")
        ->toContain("night: 'Bonsoir d’Anvers'")
        ->not->toContain('Goedenacht')
        ->not->toContain('Good night')
        ->not->toContain('Bonne nuit')
        ->not->toContain('Bon après-midi');
});

test('the register has the final button, pick and pre-launch texts, with je in Dutch and vous in French', function () {
    $data = openingSource('opening-data.ts');
    $register = openingSource('opening-register.tsx');

    expect($data)
        ->toContain("chooseNumber: 'Kies je nummer'")
        ->toContain("claimNumber: 'Reserveer Nº {n}'")
        ->toContain("claimNumber: 'Claim Nº {n}'")
        ->toContain("claimNumber: 'Réserver le Nº {n}'")
        ->toContain("joinList: 'Schrijf je in'")
        ->toContain("joinList: 'Join the list'")
        ->toContain("joinList: 'M’inscrire'")
        ->toContain("pickedLine: 'Nº {n} — het jouwe, als je het kiest.'")
        ->toContain("enterHouse: 'Kom binnen'")
        ->not->toContain('roomKicker')
        ->not->toContain('← Terug naar het huis')
        ->toContain("archiveTooltip: 'La pièce de la Maison'")
        ->not->toContain("'Kies uw")
        ->not->toContain('Kies jouw')
        ->not->toContain('Betreed');

    expect($register)
        ->toContain('copy.joinList')
        ->toContain('openNewsletter()')
        ->toContain('copy.claimNumber')
        ->toContain('copy.pickedLine');
});

test('the top bar is one strip with its own background, so Skip and the language switch never cover content', function () {
    $css = File::get(resource_path('css/opening.css'));

    expect($css)
        ->toMatch('/\.op-top\s*\{[^}]*justify-content:\s*space-between;[^}]*border-bottom:/')
        ->not->toMatch('/\.op-lang\s*\{[^}]*position:\s*fixed/');
});
