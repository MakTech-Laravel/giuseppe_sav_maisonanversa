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
        ->toContain('LegalHtml')
        ->toContain('Section')
        ->toContain('Wrap')
        ->toContain('useTranslation')
        ->not->toContain('whitespace-pre-line')
        ->not->toContain('onclick=');

    foreach (['privacy', 'terms', 'shipping', 'care'] as $page) {
        $source = file_get_contents(resource_path("js/pages/maison/legal/{$page}.tsx"));

        expect($source)
            ->toContain('LegalPageLayout')
            ->not->toContain('PageScaffold')
            ->not->toContain('onclick=');
    }
});

test('the legal editor defaults to tiptap with an html toggle and preview', function () {
    $editor = file_get_contents(resource_path('js/components/admin/legal-rich-text-editor.tsx'));
    $source = file_get_contents(resource_path('js/components/admin/legal-html-source-editor.tsx'));
    $edit = file_get_contents(resource_path('js/pages/admin/legal-pages/edit.tsx'));

    expect($editor)
        ->toContain('immediatelyRender: false')
        ->toContain("t('HTML')")
        ->toContain("t('Voorbeeld')")
        ->toContain('sanitizeLegalHtml')
        ->toContain('handlePaste')
        ->toContain('LegalHtmlSourceEditor')
        ->toContain("t('Opmaak wissen')")
        ->toContain('TextStyleKit')
        ->toContain('setColor')
        ->toContain('setBackgroundColor')
        ->toContain('setFontFamily')
        ->toContain('setFontSize')
        ->toContain('heading: { levels: [1, 2, 3, 4, 5, 6] }')
        ->toContain("t('Kop 1')")
        ->toContain("t('Uitklapbaar')")
        ->and($source)
        ->toContain("t('Opmaken')")
        ->toContain('prettyPrintLegalHtml')
        ->and($edit)
        ->toContain('LegalRichTextEditor')
        ->not->toContain('form.transform(');
});

test('legal cross-links use maison links instead of hash onclick handlers', function () {
    $links = file_get_contents(resource_path('js/components/maison/legal/legal-page-links.tsx'));

    expect($links)
        ->toContain('MaisonLink')
        ->toContain('to="contact"')
        ->toContain('to="care"')
        ->toContain('to="shipping"');
});
