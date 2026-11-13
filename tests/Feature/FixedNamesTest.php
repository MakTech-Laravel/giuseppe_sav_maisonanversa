<?php

use App\Models\Product;
use Illuminate\Support\Facades\File;

test('no dictionary key remaps a fixed name to another text', function (string $locale) {
    $dictionary = json_decode(File::get(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);

    foreach ((array) config('maison.fixed_names') as $name) {
        if (array_key_exists($name, $dictionary)) {
            expect($dictionary[$name])->toBe($name);
        }
    }

    // The old room words that used to be translated are no longer dictionary keys.
    foreach (['Bureau', 'Galerie', 'Bibliotheek', 'De speelkamer', 'Onthaal · Home'] as $key) {
        expect($dictionary)->not->toHaveKey($key);
    }
})->with(['nl', 'fr']);

test('fixed names passed through t() stay identity keys when present in dictionaries', function () {
    // Screens may use t('Founding Circle') so i18next falls back to the brand
    // string; dictionaries must never remap those keys. This scan only fails
    // when a fixed name is used as a t() key and also has a non-identity entry.
    $nl = json_decode(File::get(lang_path('nl.json')), true, flags: JSON_THROW_ON_ERROR);
    $fr = json_decode(File::get(lang_path('fr.json')), true, flags: JSON_THROW_ON_ERROR);
    $offenders = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        if (! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        preg_match_all('/\bt\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $file->getContents(), $matches);

        foreach ($matches[1] as $key) {
            foreach ((array) config('maison.fixed_names') as $name) {
                if ($key !== $name) {
                    continue;
                }
                if ((array_key_exists($name, $nl) && $nl[$name] !== $name)
                    || (array_key_exists($name, $fr) && $fr[$name] !== $name)) {
                    $offenders[] = "{$file->getRelativePathname()}: {$key}";
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('the opening carries every room name unchanged in all three languages', function () {
    $source = File::get(resource_path('js/components/maison/opening/opening-data.ts'));

    foreach (['Salon', 'Bureau', 'Le Cercle', 'Galerie', 'Salle Commune', 'Bibliothèque', 'Atelier', 'Club Corner'] as $name) {
        expect((array) config('maison.fixed_names'))->toContain($name)
            ->and(substr_count($source, "name: '{$name}'"))->toBe(3);
    }
});

test('the house page takes its room names from the opening, not the dictionary', function () {
    foreach (['maison-floorplan.tsx', 'maison-room-list.tsx'] as $file) {
        $source = File::get(resource_path("js/components/maison/house/{$file}"));

        expect($source)
            ->toContain('roomText')
            ->not->toContain("t('Bureau')")
            ->not->toContain("t('Galerie')")
            ->not->toContain("t('Bibliotheek')");
    }

    expect(File::get(resource_path('js/pages/maison/contact.tsx')))->not->toContain("t('Bureau')");
});

test('an edition number is written Nº 001, never No.001', function () {
    $offenders = [];

    foreach (['app', 'resources/js', 'resources/views', 'lang'] as $directory) {
        foreach (File::allFiles(base_path($directory)) as $file) {
            if (preg_match('/(?:\bNo\.|\bN°)\s?(?:\d{3}\b|:number)/u', $file->getContents())) {
                $offenders[] = $file->getRelativePathname();
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('the founding product name is forced to the canonical text by the data migration', function () {
    $product = Product::founding();
    $product->forceFill(['name' => 'Heritage No.001 · Founding Edition'])->saveQuietly();
    $product->translations()->updateOrCreate(
        ['locale' => 'en', 'column' => 'name'],
        ['value' => 'Something translated', 'source_hash' => 'x'],
    );

    (require database_path('migrations/2026_11_06_103410_normalise_ordinal_sign_and_force_founding_product_name.php'))->up();

    $fresh = Product::founding()->fresh();

    expect($fresh->name)->toBe('Heritage Nº 001 · Founding Edition')
        ->and($fresh->translated('name', 'en'))->toBe('Heritage Nº 001 · Founding Edition')
        ->and($fresh->translated('name', 'fr'))->toBe('Heritage Nº 001 · Founding Edition');
});
