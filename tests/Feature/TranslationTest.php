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

/**
 * @return array<int, string>
 */
function canonicalDictionaryKeys(array $dictionary): array
{
    $keys = array_keys($dictionary);

    $canonical = array_values(array_filter(
        $keys,
        fn (string $key): bool => ! array_any(
            $keys,
            fn (string $other): bool => $other !== $key && str_starts_with($other, $key),
        ),
    ));

    sort($canonical);

    return $canonical;
}

test('the translated locales cover the same canonical keys', function () {
    $en = canonicalDictionaryKeys(dictionary('en'));
    $fr = canonicalDictionaryKeys(dictionary('fr'));

    $onlyEn = array_values(array_diff($en, $fr));
    $onlyFr = array_values(array_diff($fr, $en));

    expect($onlyFr)->toBeEmpty()
        ->and($onlyEn)->toBe(['Q2 2027']);
});

test('truncated orphan keys remain out of sync between translated locales', function () {
    $enOrphans = array_diff(array_keys(dictionary('en')), canonicalDictionaryKeys(dictionary('en')));
    $frOrphans = array_diff(array_keys(dictionary('fr')), canonicalDictionaryKeys(dictionary('fr')));

    expect($enOrphans)->not->toBeEmpty()
        ->and($frOrphans)->not->toBeEmpty()
        ->and(array_values($enOrphans))->not->toBe(array_values($frOrphans));
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
    expect(__('Catalogus'))->toBe('Product');
    expect(__('Product'))->toBe('Product');
    expect(__('Producten'))->toBe('Products');
    expect(__('Open evenementen'))->toBe('Open events');
    expect(__('Alleen leden'))->toBe('Members only');
    expect(__('Partnerclub'))->toBe('Partner club');
    expect(__('Community'))->toBe('Community');
    expect(__('Contact'))->toBe('Contact');
    expect(__('Padel'))->toBe('Padel');
    expect(__('Tennis'))->toBe('Tennis');

    App::setLocale('fr');
    expect(__('Het Huis'))->toBe('La Maison');
    expect(__('Catalogus'))->toBe('Produit');
    expect(__('Product'))->toBe('Produit');
    expect(__('Producten'))->toBe('Produits');
    expect(__('Open evenementen'))->toBe('Événements ouverts');
    expect(__('Alleen leden'))->toBe('Membres uniquement');
    expect(__('Partnerclub'))->toBe('Club partenaire');
    expect(__('Georganiseerd door Maison Anversa.'))->toBe('Organisés par Maison Anversa.');
    expect(__('Community'))->toBe('Communauté');
    expect(__('Contact'))->toBe('Contactez-nous');
    expect(__('Padel'))->toBe('Le padel');
    expect(__('Tennis'))->toBe('Le tennis');
});

test('an untranslated key falls back to its dutch source text', function (string $locale) {
    App::setLocale($locale);

    // The key is the Dutch copy, so a miss still renders meaningful text.
    expect(__('Een sleutel die niet bestaat'))->toBe('Een sleutel die niet bestaat');
})->with(['nl', 'en', 'fr']);

const FULL_ANVERS_STORY_KEY = '"Anvers" is de Franse naam voor Antwerpen. "Anversa" is onze Europese variatie — elegant in het Frans, Italiaans en Engels tegelijk. De naam draagt de stad. De stad draagt het merk.';

const TRUNCATED_ANVERS_ORPHAN_KEY = '"Anvers" is de Franse naam voor Antwerpen. "Anversa" is onze Europese variatie — elegant in het Frans, Italiaans en Enge';

test('the story page quotes the full source key', function () {
    $source = file_get_contents(resource_path('js/pages/maison/story.tsx'));

    expect($source)->toContain(FULL_ANVERS_STORY_KEY);
});

test('the full story key resolves in translated locales', function (string $locale) {
    App::setLocale($locale);

    expect(__(FULL_ANVERS_STORY_KEY))->not->toBe(FULL_ANVERS_STORY_KEY);
})->with(['en', 'fr']);

test('a truncated key that is absent from the dictionary falls back to dutch on translated locales', function (string $locale) {
    App::setLocale($locale);

    $partialKey = '"Anvers" is de Franse naam voor Antwerpen. "Anversa" is onze Europese variatie — elegant in het Frans, Italiaans en Engels tegelijk. De naam draagt de stad.';

    expect(__($partialKey))->toBe($partialKey);
})->with(['en', 'fr']);

test('a known truncated orphan resolves from the dictionary instead of falling back to dutch', function (string $locale) {
    App::setLocale($locale);

    expect(__(TRUNCATED_ANVERS_ORPHAN_KEY))->not->toBe(TRUNCATED_ANVERS_ORPHAN_KEY);
})->with(['en', 'fr']);
