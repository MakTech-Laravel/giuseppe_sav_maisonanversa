<?php

test('the contact page renders the maison contact component', function () {
    $this->get('/nl/contact')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('maison/contact'));
});

test('the contact bureau uses react state instead of toggleBureau onclick strings', function () {
    $source = file_get_contents(resource_path('js/components/maison/contact/contact-bureau.tsx'));

    expect($source)
        ->toContain('useState')
        ->toContain('openPanels')
        ->not->toContain('toggleBureau');
});

test('the contact bureau exposes seven collapsible panels including a map embed', function () {
    $source = file_get_contents(resource_path('js/components/maison/contact/contact-bureau.tsx'));
    $dataSource = file_get_contents(resource_path('js/components/maison/contact/contact-data.ts'));

    expect($dataSource)->toContain("'bestel'")
        ->and($dataSource)->toContain("'care'")
        ->and($dataSource)->toContain("'afspraak'")
        ->and($dataSource)->toContain("'concierge'")
        ->and($dataSource)->toContain("'boutique'")
        ->and($dataSource)->toContain("'faq'")
        ->and($dataSource)->toContain("'feedback'")
        ->and($source)->toContain('<iframe')
        ->and($dataSource)->toContain('openstreetmap.org');
});

test('the contact faq uses native details elements', function () {
    $source = file_get_contents(resource_path('js/components/maison/contact/contact-faq.tsx'));

    expect($source)
        ->toContain('<details')
        ->toContain('<summary')
        ->not->toContain('MaisonAccordion');
});

test('three bureau forms submit through a shared mailto helper', function () {
    $formSource = file_get_contents(resource_path('js/components/maison/contact/bureau-form.tsx'));
    $bureauSource = file_get_contents(resource_path('js/components/maison/contact/contact-bureau.tsx'));
    $mailtoSource = file_get_contents(resource_path('js/components/maison/contact/bureau-mailto.ts'));

    expect($mailtoSource)->toContain('submitBureauMailto')
        ->and($formSource)->toContain('submitBureauMailto')
        ->and($bureauSource)->toContain('Afspraak aanvraag')
        ->and($bureauSource)->toContain('Privé consult aanvraag')
        ->and($bureauSource)->toContain('Feedback');
});

test('the contact bureau chat renders bubble actions safely without innerHTML', function () {
    $source = file_get_contents(resource_path('js/components/maison/contact/contact-bureau.tsx'));

    expect($source)
        ->not->toContain('dangerouslySetInnerHTML')
        ->not->toContain('innerHTML');
});
