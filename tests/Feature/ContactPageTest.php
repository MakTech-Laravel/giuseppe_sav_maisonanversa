<?php

use App\Enums\InquiryType;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Mail;

test('the contact page renders the maison contact component', function () {
    $this->get('/nl/contact')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('maison/contact'));
});

test('the contact bureau uses react state instead of toggleBureau onclick strings', function () {
    $source = file_get_contents(resource_path('js/components/maison/contact/contact-bureau.tsx'));

    expect($source)
        ->toContain('useState')
        ->toContain('openPanel')
        ->toContain('togglePanel')
        ->toContain('aria-expanded')
        ->not->toContain('toggleBureau');
});

test('the contact bureau opens one panel under the chosen bubble rather than stacking every section', function () {
    $source = file_get_contents(resource_path('js/components/maison/contact/contact-bureau.tsx'));

    expect($source)
        ->toContain('BureauPanelBody')
        ->toContain('scrollIntoView')
        ->toContain('openPanel === bubble.panel')
        ->not->toContain('openPanels');
});

test('the contact bureau exposes seven collapsible panels including a map embed', function () {
    $source = file_get_contents(resource_path('js/components/maison/contact/contact-bureau.tsx'));
    $dataSource = file_get_contents(resource_path('js/components/maison/contact/contact-data.ts'));

    expect($dataSource)->toContain("'bestel'")
        ->and($dataSource)->toContain("'care'")
        ->and($dataSource)->toContain("'afspraak'")
        ->and($dataSource)->toContain("'concierge'")
        ->and($dataSource)->toContain("'atelier'")
        ->and($dataSource)->toContain("'faq'")
        ->and($dataSource)->toContain("'feedback'")
        ->and($source)->toContain('<iframe')
        ->and($source)->toContain('mapSrc');
});

test('the contact faq uses native details elements', function () {
    $source = file_get_contents(resource_path('js/components/maison/contact/contact-faq.tsx'));

    expect($source)
        ->toContain('<details')
        ->toContain('<summary')
        ->not->toContain('MaisonAccordion');
});

test('three bureau forms post contact inquiries through inertia', function () {
    $formSource = file_get_contents(resource_path('js/components/maison/contact/bureau-form.tsx'));
    $bureauSource = file_get_contents(resource_path('js/components/maison/contact/contact-bureau.tsx'));

    expect($formSource)->toContain('storeContact')
        ->and($formSource)->toContain("from '@/routes/maison/contact'")
        ->and($formSource)->not->toContain('submitBureauMailto')
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

test('a contact inquiry is persisted and queued to the bureau inbox', function () {
    Mail::fake();

    $this->post('/nl/contact', [
        'name' => 'Yusuf Savran',
        'email' => 'yusuf@example.com',
        'subject' => 'Afspraak aanvraag',
        'message' => 'Graag een atelierbezoek.',
        'datum' => '2026-09-01',
        'moment' => 'Ochtend',
    ])->assertRedirect();

    $inquiry = Inquiry::query()->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->type)->toBe(InquiryType::Contact)
        ->and($inquiry->name)->toBe('Yusuf Savran')
        ->and($inquiry->email)->toBe('yusuf@example.com')
        ->and($inquiry->subject)->toBe('Afspraak aanvraag')
        ->and($inquiry->message)->toBe('Graag een atelierbezoek.')
        ->and($inquiry->locale)->toBe('nl')
        ->and($inquiry->meta)->toMatchArray([
            'datum' => '2026-09-01',
            'moment' => 'Ochtend',
        ]);

    Mail::assertQueued(InquiryReceived::class, function (InquiryReceived $mail) use ($inquiry) {
        return $mail->inquiry->is($inquiry)
            && $mail->hasTo(config('mail.bureau_address', config('mail.from.address')));
    });
});
