<?php

test('the primary navigation connects all nine menu pages to live routes', function () {
    $source = file_get_contents(resource_path('js/lib/maison-navigation.ts'));

    preg_match(
        '/export const PRIMARY_NAV[^=]*=\s*\[(.*?)\];/s',
        $source,
        $match
    );

    expect($match[1] ?? '')->not->toBeEmpty();

    preg_match_all(
        "/\{\s*page:\s*'([^']+)',\s*label:\s*'([^']+)'\s*\}/",
        $match[1],
        $items,
        PREG_SET_ORDER
    );

    $expected = [
        'house' => 'Het Huis',
        'products' => 'Producten',
        'story' => 'Ons Verhaal',
        'circle' => 'Founding Circle',
        'dressing' => 'Kleedkamer',
        'journal' => 'Journal',
        'community' => 'Community',
        'corner' => 'Club Corner',
        'contact' => 'Contact',
    ];

    expect($items)->toHaveCount(9);

    foreach ($items as $item) {
        [, $page, $label] = $item;

        expect($expected)->toHaveKey($page)
            ->and($label)->toBe($expected[$page]);

        $this->get(route("maison.{$page}", ['locale' => 'nl']))
            ->assertOk();
    }

    $nav = file_get_contents(resource_path('js/components/maison/shell/site-nav.tsx'));

    expect($nav)
        ->toContain('PRIMARY_NAV.map')
        ->toContain('MaisonLink')
        ->toContain('to={page}');
});

test('maison pages that call useTranslation import it from react-i18next', function () {
    $pages = glob(resource_path('js/pages/maison/**/*.tsx')) ?: [];
    $pages = array_merge($pages, glob(resource_path('js/pages/maison/*.tsx')) ?: []);

    expect($pages)->not->toBeEmpty();

    foreach (array_unique($pages) as $path) {
        $source = file_get_contents($path);

        if (! str_contains($source, 'useTranslation(')) {
            continue;
        }

        expect($source)
            ->toContain("from 'react-i18next'")
            ->toContain('useTranslation');
    }
});
