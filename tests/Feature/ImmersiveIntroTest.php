<?php

use App\Support\Imagery;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Every quoted string that follows `$field:` inside `$source`, in order.
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

test('the sequence is eight rooms plus a closing card', function () {
    expect(introCopy())->toHaveCount(9);
});

test('every slide names its room in all three languages', function () {
    foreach (introCopy() as $index => $copy) {
        expect(array_keys($copy))->toBe(['nl', 'en', 'fr'], "Slide {$index}");

        foreach ($copy as $locale => $fields) {
            expect($fields['eyebrow'])->not->toBe('', "{$locale} slide {$index}")
                ->and($fields['emphasis'])->not->toBe('')
                ->and($fields['subtitle'])->not->toBe('');
        }
    }
});

test('the intro visits Founding Circle and Community before the closing card', function () {
    $destinations = quotedValues(introSource(), 'destination');

    expect($destinations)
        ->toContain('circle')
        ->toContain('community')
        ->toContain('home');
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

    expect(Imagery::existingPaths())
        ->toContain('images/rooms/room-entrance.png')
        ->toContain('images/rooms/room-library.png')
        ->toContain('images/rooms/room-atelier.png')
        ->toContain('images/rooms/room-dressing.png')
        ->toContain('images/rooms/room-coffee.png')
        ->toContain('images/rooms/room-courtyard.png');
});

test('the closing card stands in the courtyard, so its panel never re-zooms', function () {
    $rooms = quotedValues(introSource(), 'room');

    expect($rooms)->toHaveCount(9)
        ->and($rooms[array_key_last($rooms)])->toBe('room-courtyard');
});

test('the counter numbers the rooms, not the slides', function () {
    expect(File::get(resource_path('js/lib/maison-intro.ts')))
        ->toContain('export const ROOM_COUNT = INTRO_SLIDES.length - 1')
        ->toContain('export const CLOSING_SLIDE = INTRO_SLIDES.length - 1');

    expect(File::get(resource_path('js/components/maison/intro/immersive-intro.tsx')))
        ->toContain('ROOM_COUNT')
        ->toContain('CLOSING_SLIDE');
});

test('the intro keeps the idle, lock and swipe thresholds', function (string $constant, string $value) {
    expect(File::get(resource_path('js/components/maison/intro/immersive-intro.tsx')))
        ->toContain("const {$constant} = {$value};");
})->with([
    ['IDLE_ENTER_MS', '15000'],
    ['SLIDE_LOCK_MS', '800'],
    ['SWIPE', '50'],
]);

test('reduced motion is shown in rather than left staring at a still slide', function () {
    expect(File::get(resource_path('js/components/maison/intro/immersive-intro.tsx')))
        ->toContain("matchMedia('(prefers-reduced-motion: reduce)')");
});

test('the overlay decides whether to run before the first paint', function () {
    expect(File::get(resource_path('js/components/maison/intro/immersive-intro.tsx')))
        ->toContain('typeof window === \'undefined\' ? false : shouldRun()');
});

test('the boot cover stays until the curtain lifts or the visitor skips', function () {
    expect(File::get(resource_path('js/components/maison/cinematic/preloader.tsx')))
        ->not->toContain('removeBootCover')
        ->not->toContain('BOOT_COVER_ID');

    expect(File::get(resource_path('js/components/maison/intro/immersive-intro.tsx')))
        ->toContain('removeBootCover()');
});
