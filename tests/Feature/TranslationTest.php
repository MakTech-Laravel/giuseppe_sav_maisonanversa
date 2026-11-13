<?php

use App\Models\StoryPage;
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
})->with(['nl', 'fr']);

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
    $nl = canonicalDictionaryKeys(dictionary('nl'));
    $fr = canonicalDictionaryKeys(dictionary('fr'));

    $onlyNl = array_values(array_diff($nl, $fr));
    $onlyFr = array_values(array_diff($fr, $nl));

    expect($onlyFr)->toBeEmpty()
        ->and($onlyNl)->toBeEmpty();
});

test('truncated orphan keys exist in both translated locales', function () {
    $nlOrphans = array_diff(array_keys(dictionary('nl')), canonicalDictionaryKeys(dictionary('nl')));
    $frOrphans = array_diff(array_keys(dictionary('fr')), canonicalDictionaryKeys(dictionary('fr')));

    expect($nlOrphans)->not->toBeEmpty()
        ->and($frOrphans)->not->toBeEmpty();
});

test('no translation is left empty', function (string $locale) {
    $blank = array_keys(array_filter(
        dictionary($locale),
        fn (string $value): bool => trim($value) === '',
    ));

    expect($blank)->toBeEmpty();
})->with(['nl', 'fr']);

test('english is the source language and needs no dictionary file', function () {
    expect(lang_path('en.json'))->not->toBeFile();
});

test('laravel resolves the translated copy for a known key', function () {
    App::setLocale('nl');
    expect(__('The House'))->toBe('Het Huis');
    expect(__('Product'))->toBe('Product');
    expect(__('Products'))->toBe('Producten');
    expect(__('Open events'))->toBe('Open evenementen');
    expect(__('Members only'))->toBe('Alleen voor leden');
    expect(__('Maison Anversa Partner Club'))->toBe('Partnerclub van Maison Anversa');
    expect(__('Organised by {{name}}'))->toBe('Georganiseerd door {{name}}');
    expect(__('Share'))->toBe('Delen');
    expect(__('Not yet'))->toBe('Nog niet');
    expect(__('Community'))->toBe('Community');
    expect(__('Contact'))->toBe('Contact');
    expect(__('Padel'))->toBe('Padel');
    expect(__('Tennis'))->toBe('Tennis');

    App::setLocale('fr');
    expect(__('The House'))->toBe('La Maison');
    expect(__('Product'))->toBe('Produit');
    expect(__('Products'))->toBe('Produits');
    expect(__('Open events'))->toBe('Événements ouverts');
    expect(__('Members only'))->toBe('Réservé aux membres');
    expect(__('Maison Anversa Partner Club'))->toBe('Club partenaire Maison Anversa');
    expect(__('Hosted by Maison Anversa.'))->toBe('Organisé par Maison Anversa.');
    expect(__('Organised by {{name}}'))->toBe('Organisé par {{name}}');
    expect(__('Share'))->toBe('Partager');
    expect(__('Not yet'))->toBe('Pas encore');
    expect(__('Community'))->toBe('Communauté');
    expect(__('Contact'))->toBe('Contact');
    expect(__('Padel'))->toBe('Le padel');
    expect(__('Tennis'))->toBe('Le tennis');
});

test('an untranslated key falls back to its english source text', function (string $locale) {
    App::setLocale($locale);

    // The key is the English copy, so a miss still renders meaningful text.
    expect(__('A key that does not exist'))->toBe('A key that does not exist');
})->with(['nl', 'en', 'fr']);

const TRUNCATED_ANVERS_ORPHAN_KEY = '"Anvers" is the French name for Antwerp. "Anversa" is our European variation — elegant in French, Italian and En';

test('the story name section keeps the authored cms source on defaults', function () {
    expect(StoryPage::defaults()['name_body'])->toBe(
        'Anversa is the Italian name for Antwerp. We chose it for a city that has always looked beyond its borders, and for a house with roots in Antwerp and its eyes on Europe.'
    );
});

test('a truncated key that is absent from the dictionary falls back to english on translated locales', function (string $locale) {
    App::setLocale($locale);

    $partialKey = 'Anversa is the Italian name for Antwerp. We chose it for a city that always looked beyond its borders.';

    expect(__($partialKey))->toBe($partialKey);
})->with(['nl', 'fr']);

test('a known truncated orphan resolves from the dictionary instead of falling back to english', function (string $locale) {
    App::setLocale($locale);

    expect(__(TRUNCATED_ANVERS_ORPHAN_KEY))->not->toBe(TRUNCATED_ANVERS_ORPHAN_KEY);
})->with(['nl', 'fr']);
