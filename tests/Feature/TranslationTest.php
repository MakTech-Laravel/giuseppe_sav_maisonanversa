<?php

use Illuminate\Support\Facades\App;

/**
 * @return array<string, string>
 */
function dictionary(string $locale): array
{
    $path = lang_path("{$locale}.json");

    expect($path)->toBeFile();

    $decoded = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    expect($decoded)->toBeArray();

    return $decoded;
}

test('every translated locale is valid json and non-empty', function (string $locale) {
    expect(dictionary($locale))->not->toBeEmpty();
})->with(['en', 'fr']);

test('the translated locales cover exactly the same keys', function () {
    $en = array_keys(dictionary('en'));
    $fr = array_keys(dictionary('fr'));

    sort($en);
    sort($fr);

    expect($en)->toBe($fr);
});

test('no translation is left empty', function (string $locale) {
    $blank = array_keys(array_filter(
        dictionary($locale),
        fn (string $value): bool => trim($value) === '',
    ));

    expect($blank)->toBeEmpty();
})->with(['en', 'fr']);

test('dutch is the source language and needs no dictionary file', function () {
    expect(lang_path('nl.json'))->not->toBeFile();
});

test('laravel resolves the translated copy for a known key', function () {
    App::setLocale('en');
    expect(__('Het Huis'))->toBe('The House');

    App::setLocale('fr');
    expect(__('Het Huis'))->toBe('La Maison');
});

test('an untranslated key falls back to its dutch source text', function (string $locale) {
    App::setLocale($locale);

    // The key is the Dutch copy, so a miss still renders meaningful text.
    expect(__('Een sleutel die niet bestaat'))->toBe('Een sleutel die niet bestaat');
})->with(['nl', 'en', 'fr']);
