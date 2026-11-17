<?php

use App\Support\Imagery;
use Illuminate\Support\Facades\File;

test('the home page receives the edition figures from inventory', function () {
    $this->get('/nl')->assertOk()->assertInertia(fn ($page) => $page
        ->component('maison/home')
        ->where('edition.reserved', 0)
        ->where('edition.total', 100)
        ->where('edition.available', 99)
        ->where('edition.soldOut', false)
    );
});

test('available stock is the unsold sellable pieces', function () {
    $this->get('/nl')->assertInertia(fn ($page) => $page
        ->where('edition.available', 99)
        ->where('edition.sellable', 99)
        ->where('edition.archived', 1)
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
        ->toContain('Built for generations')
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

test('the home hero eyebrow tagline and ghost links use warm ivory cream', function () {
    $source = file_get_contents(resource_path('js/components/maison/home/home-hero.tsx'));

    expect($source)
        ->toContain('tracking-[0.4em] text-cream uppercase before:inline-block')
        ->toContain('tracking-[0.35em] text-cream uppercase')
        ->toContain('text-cream border-cream/35')
        ->not->toContain('tracking-[0.4em] text-gold')
        ->not->toContain('tracking-[0.35em] text-gold');
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
        ->toContain('Pieces Only')
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
        ->toContain('Our story')
        ->toContain('European Heritage')
        ->toContain('+')
        ->toContain('Maison Anversa')
        ->toContain('European Heritage Sports and Lifestyle House')
        ->toContain('Antwerp · Belgium · Est. 2026')
        ->toContain('bg-choc2')
        ->toContain('rounded-full border border-gold')
        ->not->toContain('hidden flex-col');
});

test('homepage verhaalteksten match the revision_2 PDF in all locales', function () {
    $expected = [
        'Why we exist' => [
            'nl' => 'Waarom we bestaan',
            'fr' => 'Pourquoi nous existons',
        ],
        "Style doesn't stop where sport begins." => [
            'nl' => 'Stijl stopt niet waar sport begint.',
            'fr' => "Le style ne s'arrête pas là où commence le sport.",
        ],
        'Our story' => [
            'nl' => 'Ons verhaal',
            'fr' => 'Notre histoire',
        ],
        'Everything begins with a coffee.' => [
            'nl' => 'Alles begint bij een koffie.',
            'fr' => 'Tout commence par un café.',
        ],
        'Coffee. Style. The city. And then the court. Maison Anversa did not start from a business plan, but from a love of coffee, fashion, history and sport. One house where sport, style, culture and craftsmanship come together.' => [
            'nl' => 'Koffie. Stijl. De stad. En dan de baan. Maison Anversa ontstond niet uit een businessplan, maar uit een liefde voor koffie, mode, geschiedenis en sport. Eén huis waarin sport, stijl, cultuur en vakmanschap samenkomen.',
            'fr' => "Un café. Le style. La ville. Puis le terrain. Maison Anversa n'est pas née d'un business plan, mais d'un amour pour le café, la mode, l'histoire et le sport. Une maison où se rencontrent sport, style, culture et savoir-faire.",
        ],
        'Read our story →' => [
            'nl' => 'Lees ons verhaal →',
            'fr' => 'Lire notre histoire →',
        ],
        'Rooted in Antwerp' => [
            'nl' => 'Geworteld in Antwerpen',
            'fr' => 'Enracinée à Anvers',
        ],
        'A city that always looked outward.' => [
            'nl' => 'Een stad die altijd naar buiten keek.',
            'fr' => 'Une ville tournée vers le monde.',
        ],
        'Every day here begins the same way: a coffee while the city wakes up. Antwerp is a port city where trade, culture, fashion and ideas meet. Historic without standing still. Anversa is the Italian name for Antwerp: a house rooted in this city, looking out over Europe.' => [
            'nl' => 'Elke dag begint hier op dezelfde manier: een koffie terwijl de stad wakker wordt. Antwerpen is een havenstad waar handel, cultuur, mode en ideeën samenkomen. Historisch zonder stil te staan. Anversa is de Italiaanse naam voor Antwerpen: een huis met wortels in deze stad en een blik op Europa.',
            'fr' => "Ici, chaque journée commence de la même façon : un café pendant que la ville s'éveille. Anvers est une ville portuaire où se croisent commerce, culture, mode et idées. Historique sans jamais rester immobile. Anversa est le nom italien d'Anvers : une maison enracinée dans cette ville, le regard tourné vers l'Europe.",
        ],
        'City that carries us' => [
            'nl' => 'Stad die ons draagt',
            'fr' => 'Ville qui nous porte',
        ],
        "Founder's Letter" => [
            'nl' => 'Oprichtersbrief',
            'fr' => 'Lettre du fondateur',
        ],
        'The story of Maison Anversa. Written by the founder.' => [
            'nl' => 'Het verhaal van Maison Anversa. Geschreven door de oprichter.',
            'fr' => "L'histoire de Maison Anversa. Écrite par le fondateur.",
        ],
        'The founder' => [
            'nl' => 'De oprichter',
            'fr' => 'Le fondateur',
        ],
        'The vision. The story. The mission.' => [
            'nl' => 'De visie. Het verhaal. De missie.',
            'fr' => "La vision. L'histoire. La mission.",
        ],
        'A house built from conviction, step by step.' => [
            'nl' => 'Een huis gebouwd vanuit overtuiging, stap voor stap.',
            'fr' => 'Une maison bâtie par conviction, pas à pas.',
        ],
    ];

    $bySection = [
        'home-intro.tsx' => [
            'Why we exist',
            "Style doesn't stop where sport begins.",
        ],
        'home-story.tsx' => [
            'Our story',
            'Everything begins with a coffee.',
            'Coffee. Style. The city. And then the court. Maison Anversa did not start from a business plan, but from a love of coffee, fashion, history and sport. One house where sport, style, culture and craftsmanship come together.',
            'Read our story →',
        ],
        'home-antwerp.tsx' => [
            'Rooted in Antwerp',
            'A city that always looked outward.',
            'Every day here begins the same way: a coffee while the city wakes up. Antwerp is a port city where trade, culture, fashion and ideas meet. Historic without standing still. Anversa is the Italian name for Antwerp: a house rooted in this city, looking out over Europe.',
            'City that carries us',
        ],
        'home-unboxing.tsx' => [
            "Founder's Letter",
            'The story of Maison Anversa. Written by the founder.',
        ],
        'home-content-grid.tsx' => [
            'The founder',
            'The vision. The story. The mission.',
            'A house built from conviction, step by step.',
            'Read our story →',
        ],
    ];

    $nl = json_decode(File::get(lang_path('nl.json')), true, flags: JSON_THROW_ON_ERROR);
    $fr = json_decode(File::get(lang_path('fr.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach ($bySection as $file => $keys) {
        $source = File::get(resource_path("js/components/maison/home/{$file}"));

        foreach ($keys as $key) {
            expect($source)->toContain($key);
            expect($nl[$key] ?? null)->toBe($expected[$key]['nl']);
            expect($fr[$key] ?? null)->toBe($expected[$key]['fr']);
        }
    }

    $manifesto = File::get(resource_path('js/components/maison/home/home-manifesto.tsx'));

    expect($manifesto)
        ->toContain('Every legacy begins with a first chapter.')
        ->not->toContain('t(');

    foreach (File::files(resource_path('js/components/maison/home')) as $file) {
        if (! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        expect($file->getContents())->not->toContain('Yusuf');
    }
});

test('the document sets the right light and the once-per-session flag before the first paint', function () {
    $this->get('/nl')
        ->assertOk()
        ->assertSee('root.dataset.light', false)
        ->assertSee('maison.opening.seen', false)
        ->assertDontSee('maison-boot-cover', false);
});
