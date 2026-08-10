<?php

test('the legal pages render their Inertia components', function (string $path, string $component) {
    $this->get($path)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    ['/nl/privacy', 'maison/legal/privacy'],
    ['/nl/terms', 'maison/legal/terms'],
    ['/nl/shipping', 'maison/legal/shipping'],
    ['/nl/care', 'maison/legal/care'],
]);

test('the legal pages share a layout component and avoid onclick navigation', function () {
    $layout = file_get_contents(resource_path('js/components/maison/legal/legal-page-layout.tsx'));

    expect($layout)
        ->toContain('PageHero')
        ->toContain('Section')
        ->toContain('Wrap')
        ->toContain('useTranslation')
        ->not->toContain('onclick=');

    foreach (['privacy', 'terms', 'shipping', 'care'] as $page) {
        $source = file_get_contents(resource_path("js/pages/maison/legal/{$page}.tsx"));

        expect($source)
            ->toContain('LegalPageLayout')
            ->toContain('useTranslation')
            ->not->toContain('PageScaffold')
            ->not->toContain('onclick=');
    }
});

test('legal cross-links use maison links instead of hash onclick handlers', function () {
    $privacy = file_get_contents(resource_path('js/pages/maison/legal/privacy.tsx'));
    $terms = file_get_contents(resource_path('js/pages/maison/legal/terms.tsx'));
    $care = file_get_contents(resource_path('js/pages/maison/legal/care.tsx'));

    expect($privacy)->toContain('MaisonLink')->toContain("to=\"contact\"")
        ->and($terms)->toContain("to=\"care\"")->toContain("to=\"shipping\"")
        ->and($care)->toContain("to=\"contact\"");
});
