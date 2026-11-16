<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\File;

/*
 * lang/nl.json and lang/fr.json are keyed by English text and English has no dictionary, so a
 * key that is Dutch or missing silently renders Dutch on the other languages. These tests read
 * the keys back out of the source (PHP, Blade and the public React pages) and fail on that.
 * Admin screens are out of scope here; the Maison components are covered by ShellTranslationKeysTest.
 */

/** Brand names that read the same in every language and carry no dictionary entry. */
const COVERAGE_BRAND_KEYS = [
    'Atelier',
    'Club Corner',
    'Founding Circle',
    'Founding Edition',
    'Heritage Certificate',
    'Heritage Letter',
    'Heritage No.001',
    'Heritage Nº 001',
    'Heritage Passport',
    'Heritage brochures',
    'Journal',
    'Maison Anversa',
    'The',
];

/** Dutch function words that never occur in an English source key. */
const COVERAGE_DUTCH_WORDS = ['het', 'een', 'voor', 'niet', 'jouw', 'wij', 'bent', 'wordt', 'heeft', 'deze', 'geen', 'naar', 'ook', 'maar'];

/**
 * @return array<string, mixed>
 */
function coverageDictionary(string $locale): array
{
    return json_decode(File::get(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * Every literal `__('…')` / `t('…')` key outside the admin screens, grouped by file.
 *
 * @return array<string, list<string>>
 */
function coveragePublicKeys(): array
{
    $skip = [
        '/pages/admin/', '/components/admin/', '/Controllers/Admin/', '/Requests/Admin/', '/Monitoring/', '/Console/',
        '/resources/js/actions/', '/resources/js/routes/', '/resources/js/wayfinder/', '/pages/settings/',
        '/components/ui/', '/pages/dashboard.tsx', '/components/app-sidebar.tsx',
    ];

    $keys = [];

    foreach (['app', 'resources/views', 'resources/js'] as $directory) {
        foreach (File::allFiles(base_path($directory)) as $file) {
            $path = str_replace('\\', '/', $file->getPathname());

            if (! in_array($file->getExtension(), ['php', 'ts', 'tsx'], true)
                || collect($skip)->contains(fn (string $part): bool => str_contains($path, $part))) {
                continue;
            }

            $contents = $file->getContents();
            preg_match_all("/(?:\\b__|\\bt)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", $contents, $single);
            preg_match_all('/(?:\b__|\bt)\(\s*"((?:[^"\\\\]|\\\\.)*)"/', $contents, $double);

            $found = collect([...$single[1], ...$double[1]])
                ->map(fn (string $key): string => stripcslashes($key))
                ->reject(fn (string $key): bool => coverageKeyIsOnlyPlaceholders($key))
                ->unique()
                ->values()
                ->all();

            if ($found !== []) {
                $keys[str_replace('\\', '/', $file->getRelativePathname())] = $found;
            }
        }
    }

    return $keys;
}

function coverageKeyIsOnlyPlaceholders(string $key): bool
{
    return preg_replace('/:[a-z_]+|\{\{?\w+\}?\}|[\s·—–\-\/|.,]+|\d+/iu', '', $key) === '';
}

test('every key used by the public pages, mails and backend exists in both dictionaries', function (string $locale) {
    $dictionary = coverageDictionary($locale);
    $missing = [];

    foreach (coveragePublicKeys() as $file => $keys) {
        foreach ($keys as $key) {
            if (! in_array($key, COVERAGE_BRAND_KEYS, true) && ! array_key_exists($key, $dictionary)) {
                $missing[] = "{$file}: {$key}";
            }
        }
    }

    expect($missing)->toBe([], "Keys with no {$locale} translation:\n".implode("\n", $missing));
})->with(['nl', 'fr']);

test('no Dutch literal is used as a translation key', function () {
    $dutch = [];

    foreach (coveragePublicKeys() as $file => $keys) {
        foreach ($keys as $key) {
            $words = preg_split('/[^\p{L}]+/u', mb_strtolower($key), -1, PREG_SPLIT_NO_EMPTY);

            if (array_intersect($words, COVERAGE_DUTCH_WORDS) !== []) {
                $dutch[] = "{$file}: {$key}";
            }
        }
    }

    expect($dutch)->toBe([], "Dutch keys leak on every locale except nl:\n".implode("\n", $dutch));
});

test('the dictionaries no longer carry the outdated shop, shipping and atelier copy', function (string $locale) {
    // 30-day returns, worldwide / Benelux / non-EU shipping, a fixed A6 / 24-page passport,
    // an atelier to visit and a phone line were all removed in the November 2026 review.
    $banned = '/30[- ]day return|return within 30|30 dagen retour|retour binnen 30|retour sous 30|retour 30 jours'
        .'|worldwide|wereldwijd|dans le monde entier|benelux|outside europe|buiten europa|hors d.europe'
        .'|\bA6\b|24 pages|24 pagina|atelier visit|our atelier|notre atelier|ons atelier|call us|bel ons|appelez-nous/iu';

    $offenders = [];

    foreach (coverageDictionary($locale) as $key => $value) {
        if (preg_match($banned, $key) === 1 || preg_match($banned, (string) $value) === 1) {
            $offenders[] = $key;
        }
    }

    expect($offenders)->toBe([]);
})->with(['nl', 'fr']);

test('the Dutch opening copy contains no English sentences', function () {
    $source = File::get(resource_path('js/components/maison/opening/opening-data.ts'));
    preg_match('/export const OPENING_COPY.*?\n    nl: \{(.*?)\n    \},\n    en: \{/s', $source, $block);

    expect($block[1] ?? '')->not->toBeEmpty()
        ->toContain('Goedenavond uit Antwerpen')
        ->not->toContain('Good evening')
        ->not->toContain('Limited to {total}')
        ->not->toContain("'The register'")
        ->not->toContain("'Reserved'")
        ->not->toContain('Every lit window')
        ->not->toContain('Welcome to Maison Anversa');
});

test('the contact page offers no atelier visit, appointment or phone line', function () {
    $data = File::get(resource_path('js/components/maison/contact/contact-data.ts'));
    $bureau = File::get(resource_path('js/components/maison/contact/contact-bureau.tsx'));

    foreach ([$data, $bureau] as $source) {
        expect($source)
            ->not->toContain('Call us')
            ->not->toContain('Book an appointment')
            ->not->toContain('Find our atelier')
            ->not->toContain('our atelier');
    }
});

test('the placeholder phone number is not shared with the storefront', function () {
    $share = SiteSetting::current()->toShare();

    expect($share['phone'])->toBeNull()
        ->and($share['phoneHref'])->toBeNull();

    SiteSetting::current()->update(['phone' => '+32123456789']);

    expect(SiteSetting::current()->toShare()['phoneHref'])->toBe('tel:+32123456789');
});
