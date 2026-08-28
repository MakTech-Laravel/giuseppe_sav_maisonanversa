<?php

use App\Support\Imagery;

test('the footer brand mark uses the logo-icon photograph', function () {
    expect(public_path('images/logos/logo-icon.jpg'))->toBeFile();

    $source = file_get_contents(resource_path('js/components/maison/shell/site-footer.tsx'));

    expect($source)
        ->toContain('asset="logo-icon"')
        ->toContain('Maison Anversa')
        ->toContain('European Heritage Sports and Lifestyle House')
        ->toContain('ma-lg:grid-cols-[2fr_1fr_1fr_1fr]')
        ->toContain('ma-md:grid-cols-2')
        ->toContain('grid-cols-1');

    expect(Imagery::existingPaths())
        ->toContain('images/logos/logo-icon.jpg');
});

test('the footer support column includes faq between care and privacy', function () {
    $source = file_get_contents(resource_path('js/lib/maison-navigation.ts'));

    expect($source)
        ->toContain("label: 'FAQ'")
        ->toContain("label: 'Heritage Letter'");
});

test('the footer follow column reads instagram and press from site settings', function () {
    $navigation = file_get_contents(resource_path('js/lib/maison-navigation.ts'));
    $footer = file_get_contents(resource_path('js/components/maison/shell/site-footer.tsx'));

    expect($navigation)
        ->toContain("channel: 'instagram'")
        ->toContain("channel: 'press'");

    expect($footer)
        ->toContain('site.instagramUrl')
        ->toContain('site.emailPressHref');
});

test('the antwerp etching band sits below the site footer', function () {
    $source = file_get_contents(resource_path('js/layouts/frontend-layout.tsx'));

    expect(strpos($source, '<SiteFooter'))
        ->toBeLessThan(strpos($source, '<EtchingBand'));

    expect(public_path('images/editorial/antwerp-ets-band.png'))->toBeFile();
});

test('the contact dock button uses the maison facade house photograph', function () {
    expect(public_path('images/brand/maison-facade-house.png'))->toBeFile();

    $source = file_get_contents(resource_path('js/components/maison/shell/contact-dock.tsx'));

    expect($source)
        ->toContain('asset="maison-facade-house"')
        ->toContain('rounded-full');

    expect(Imagery::existingPaths())
        ->toContain('images/brand/maison-facade-house.png');
});
