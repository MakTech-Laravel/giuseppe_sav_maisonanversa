<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/*
 * The intro is the only place on the site whose copy does not come from the
 * dictionary: the prototype assembled it in script from an array carrying its
 * own `_en` and `_fr` fields, so nothing about it can be checked by the
 * translation-key tests. These read both arrays and compare them room by room.
 */

/**
 * Every quoted string that follows `$field:` inside `$source`, in order.
 *
 * Both files quote with whichever mark keeps the string readable, so an
 * apostrophe in "Hall d'Entrée" arrives in double quotes.
 *
 * @return list<string>
 */
function quotedValues(string $source, string $field): array
{
    preg_match_all(
        '/\b'.preg_quote($field, '/').':\s*(["\'])((?:[^"\'\\\\]|\\\\.|(?!\1)["\'])*)\1/u',
        $source,
        $matches
    );

    return array_map(fn (string $value) => stripcslashes($value), $matches[2]);
}

/** `null` where the field is literally null, so the arrays stay aligned. */
function nullableValues(string $source, string $field): array
{
    preg_match_all(
        '/\b'.preg_quote($field, '/').':\s*(?:\'([^\']*)\'|(null))/u',
        $source,
        $matches,
        PREG_SET_ORDER
    );

    return array_map(
        fn (array $match) => ($match[2] ?? '') === 'null' ? null : $match[1],
        $matches
    );
}

/** The prototype's `SLIDES` array, on its own. */
function prototypeSlides(): string
{
    return (string) Str::of(File::get(base_path('prototype/index.html')))
        ->after('const SLIDES = [')
        ->before('];');
}

/** Our `INTRO_SLIDES` array, on its own. */
function introSource(): string
{
    return (string) Str::of(File::get(resource_path('js/lib/maison-intro.ts')))
        ->after('export const INTRO_SLIDES')
        ->before('];');
}

/**
 * The intro copy, as `[slide][locale] => [eyebrow, opening, emphasis, subtitle]`.
 *
 * The three locale blocks appear in a fixed order inside each slide, so reading
 * them in document order and grouping by three reassembles the array.
 *
 * @return list<array<string, array<string, string>>>
 */
function introCopy(): array
{
    preg_match_all(
        '/\b(nl|en|fr): \{\s*eyebrow:\s*(["\'])(?<eyebrow>(?:[^"\'\\\\]|\\\\.|(?!\2)["\'])*)\2,\s*'
        .'opening:\s*(["\'])(?<opening>(?:[^"\'\\\\]|\\\\.|(?!\4)["\'])*)\4,\s*'
        .'emphasis:\s*(["\'])(?<emphasis>(?:[^"\'\\\\]|\\\\.|(?!\6)["\'])*)\6,\s*'
        .'subtitle:\s*(["\'])(?<subtitle>(?:[^"\'\\\\]|\\\\.|(?!\8)["\'])*)\8,/u',
        introSource(),
        $matches,
        PREG_SET_ORDER
    );

    $slides = [];

    foreach ($matches as $index => $match) {
        $slides[intdiv($index, 3)][$match[1]] = [
            'eyebrow' => stripcslashes($match['eyebrow']),
            'opening' => stripcslashes($match['opening']),
            'emphasis' => stripcslashes($match['emphasis']),
            'subtitle' => stripcslashes($match['subtitle']),
        ];
    }

    return $slides;
}

test('the sequence is the prototype\'s seven slides', function () {
    expect(introCopy())->toHaveCount(7)
        ->and(quotedValues(prototypeSlides(), 'eye'))->toHaveCount(7);
});

test('every slide names its room in all three languages', function () {
    foreach (introCopy() as $index => $copy) {
        expect(array_keys($copy))->toBe(['nl', 'en', 'fr'], "Slide {$index}");

        foreach ($copy as $locale => $fields) {
            /*
             * `opening` is the only field allowed to be blank, and only in
             * French, where "L'Atelier" leaves nothing before the emphasis.
             */
            expect($fields['eyebrow'])->not->toBe('', "{$locale} slide {$index}")
                ->and($fields['emphasis'])->not->toBe('')
                ->and($fields['subtitle'])->not->toBe('');
        }
    }
});

test('the room names match the prototype, in every language', function (
    string $locale,
    string $prototypeField,
) {
    $prototype = quotedValues(prototypeSlides(), $prototypeField);

    foreach (introCopy() as $index => $copy) {
        // The prototype stored one string with `<em>` in it; we store the parts.
        $reassembled = sprintf(
            '%s<em>%s</em>',
            $copy[$locale]['opening'],
            $copy[$locale]['emphasis'],
        );

        expect($reassembled)->toBe($prototype[$index], "Slide {$index}");
    }
})->with([
    ['nl', 'name'],
    ['en', 'name_en'],
    ['fr', 'name_fr'],
]);

test('the eyebrows and subtitles match the prototype too', function (
    string $locale,
    string $field,
    string $prototypeField,
) {
    $prototype = quotedValues(prototypeSlides(), $prototypeField);

    foreach (introCopy() as $index => $copy) {
        expect($copy[$locale][$field])->toBe($prototype[$index], "Slide {$index}");
    }
})->with([
    ['nl', 'eyebrow', 'eye'],
    ['en', 'eyebrow', 'eye_en'],
    ['fr', 'eyebrow', 'eye_fr'],
    ['nl', 'subtitle', 'sub'],
    ['en', 'subtitle', 'sub_en'],
    ['fr', 'subtitle', 'sub_fr'],
]);

test('each room leads where the prototype sent it', function () {
    expect(nullableValues(introSource(), 'destination'))
        ->toBe(nullableValues(prototypeSlides(), 'page'));
});

test('no slide carries markup, because the emphasis is split out instead', function () {
    foreach (introCopy() as $index => $copy) {
        foreach ($copy as $fields) {
            foreach ($fields as $value) {
                expect($value)->not->toMatch('/<[a-z\/]/i', "Slide {$index}");
            }
        }
    }
});

test('the six panels are the prototype\'s six room photographs, in order', function () {
    preg_match_all(
        '/background-image:url\((images\/rooms\/[^)]+)\)/',
        File::get(base_path('prototype/index.html')),
        $prototype
    );

    $rooms = array_values(array_unique(quotedValues(introSource(), 'room')));
    $manifest = File::get(resource_path('js/lib/imagery.ts'));

    $paths = array_map(function (string $room) use ($manifest) {
        preg_match(
            '/\''.preg_quote($room, '/').'\': \{\s*path: \'([^\']+)\'/',
            $manifest,
            $found
        );

        return $found[1] ?? null;
    }, $rooms);

    expect($paths)->toBe($prototype[1]);
});

test('every intro room photograph is on disk under public/images/rooms', function () {
    $rooms = [
        'room-entrance',
        'room-library',
        'room-atelier',
        'room-dressing',
        'room-coffee',
        'room-courtyard',
    ];

    foreach ($rooms as $room) {
        expect(public_path("images/rooms/{$room}.png"))->toBeFile();
    }

    expect(\App\Support\Imagery::existingPaths())
        ->toContain('images/rooms/room-entrance.png')
        ->toContain('images/rooms/room-library.png')
        ->toContain('images/rooms/room-atelier.png')
        ->toContain('images/rooms/room-dressing.png')
        ->toContain('images/rooms/room-coffee.png')
        ->toContain('images/rooms/room-courtyard.png');
});

test('the closing card stands in the courtyard, so its panel never re-zooms', function () {
    $rooms = quotedValues(introSource(), 'room');

    expect($rooms)->toHaveCount(7)
        ->and($rooms[6])->toBe($rooms[5]);
});

test('the counter numbers the rooms, not the slides', function () {
    /*
     * The prototype's counter read "01 / 06" over seven slides, because the
     * closing card is a threshold rather than a room. Here both the total and
     * the closing index are derived from the array, so they cannot disagree the
     * way a hardcoded "06" could.
     */
    expect(File::get(resource_path('js/lib/maison-intro.ts')))
        ->toContain('export const ROOM_COUNT = INTRO_SLIDES.length - 1')
        ->toContain('export const CLOSING_SLIDE = INTRO_SLIDES.length - 1');

    expect(File::get(resource_path('js/components/maison/intro/immersive-intro.tsx')))
        ->toContain('ROOM_COUNT')
        ->toContain('CLOSING_SLIDE');
});

test('the intro keeps the prototype\'s thresholds', function (string $constant, string $value) {
    expect(File::get(resource_path('js/components/maison/intro/immersive-intro.tsx')))
        ->toContain("const {$constant} = {$value};");
})->with([
    // 15s unattended, one slide per 800ms crossfade, a 50px swipe.
    ['IDLE_ENTER_MS', '15000'],
    ['SLIDE_LOCK_MS', '800'],
    ['SWIPE', '50'],
]);

test('reduced motion is shown in rather than left staring at a still slide', function () {
    /*
     * The one behaviour of the intro that cannot be inferred from its copy: with
     * the zoom, the drift and the crossfade all suppressed there is nothing left
     * of a room but a caption, so the visitor goes straight to the home page.
     */
    expect(File::get(resource_path('js/components/maison/intro/immersive-intro.tsx')))
        ->toContain("matchMedia('(prefers-reduced-motion: reduce)')");
});
