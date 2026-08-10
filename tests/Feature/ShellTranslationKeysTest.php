<?php

use Illuminate\Support\Facades\File;

/*
 * The dictionaries are keyed by their Dutch source string, so a typo in a `t()`
 * call is invisible at runtime: i18next falls back to the key and the Dutch text
 * renders on a French page. These tests read the keys back out of the components
 * and fail if any of them is missing from a dictionary.
 */

/**
 * Brand vocabulary that reads the same in all three languages.
 *
 * The prototype left these in English even inside Dutch and French sentences
 * ("Membre du Founding Circle"), so they carry no dictionary entry and falling
 * back to the key is the correct result rather than a missing translation.
 */
const MAISON_UNTRANSLATED_BRAND_TERMS = [
    'Club Corner',
    'Community',
    'Founding Circle',
    'Founding Edition',
    'Heritage No.001',
    'Journal',
];

/**
 * Every literal translation key used by the Maison components.
 *
 * @return array<string, list<string>> keys, grouped by the file that uses them
 */
function maisonTranslationKeys(): array
{
    $keys = [];

    $files = collect(File::allFiles(resource_path('js')))
        ->filter(fn ($file) => str_contains(
            str_replace('\\', '/', $file->getPathname()),
            '/maison'
        ))
        ->filter(fn ($file) => in_array($file->getExtension(), ['ts', 'tsx'], true));

    foreach ($files as $file) {
        $contents = $file->getContents();
        $found = [];

        /*
         * `t('…')` with a single-quoted literal. Keys carrying an apostrophe are
         * escaped in source as \', which JSON stores unescaped.
         */
        preg_match_all("/\bt\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $contents, $calls);

        foreach ($calls[1] as $key) {
            $found[] = stripcslashes($key);
        }

        /*
         * Labels and headings in the navigation manifest are passed to `t()`
         * indirectly, so the literal never appears beside a `t(` to be matched.
         */
        preg_match_all("/(?:label|heading):\s*'((?:[^'\\\\]|\\\\.)*)'/", $contents, $manifest);

        foreach ($manifest[1] as $key) {
            $found[] = stripcslashes($key);
        }

        if ($found !== []) {
            $keys[$file->getFilename()] = array_values(array_unique($found));
        }
    }

    return $keys;
}

test('the components use translation keys at all', function () {
    $keys = maisonTranslationKeys();

    expect($keys)->not->toBeEmpty();
    expect(array_merge(...array_values($keys)))->not->toBeEmpty();
});

test('every translation key the components use exists in the dictionary', function (string $locale) {
    $dictionary = json_decode(
        File::get(lang_path("{$locale}.json")),
        true,
        flags: JSON_THROW_ON_ERROR
    );

    $missing = [];

    foreach (maisonTranslationKeys() as $file => $keys) {
        foreach ($keys as $key) {
            if (in_array($key, MAISON_UNTRANSLATED_BRAND_TERMS, true)) {
                continue;
            }

            if (! array_key_exists($key, $dictionary)) {
                $missing[] = "{$file}: {$key}";
            }
        }
    }

    expect($missing)->toBe([], "Keys with no {$locale} translation:\n".implode("\n", $missing));
})->with(['en', 'fr']);

test('the brand allowlist only covers terms the dictionary really omits', function (string $locale) {
    $dictionary = json_decode(
        File::get(lang_path("{$locale}.json")),
        true,
        flags: JSON_THROW_ON_ERROR
    );

    $translatedAfterAll = array_values(array_filter(
        MAISON_UNTRANSLATED_BRAND_TERMS,
        fn (string $term) => array_key_exists($term, $dictionary)
    ));

    expect($translatedAfterAll)->toBe([]);
})->with(['en', 'fr']);

test('no translation key carries markup, because the dictionary holds plain text', function () {
    $withMarkup = [];

    foreach (maisonTranslationKeys() as $file => $keys) {
        foreach ($keys as $key) {
            if (preg_match('/<[a-z\/]/i', $key) === 1) {
                $withMarkup[] = "{$file}: {$key}";
            }
        }
    }

    expect($withMarkup)->toBe([]);
});
