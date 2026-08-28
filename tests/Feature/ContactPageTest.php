<?php

use App\Enums\InquiryType;
use App\Mail\InquiryConfirmation;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\Inquiry\InquiryDeviceCookie;
use Illuminate\Support\Facades\Mail;

test('the contact page renders the maison contact component', function () {
    $this->get('/nl/contact')
        ->assertOk()
        ->assertCookie(InquiryDeviceCookie::NAME)
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
        ->and($bureauSource)->toContain('kind="appointment"')
        ->and($bureauSource)->toContain('kind="consult"')
        ->and($bureauSource)->toContain('kind="feedback"');
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
        'kind' => InquiryType::Appointment->value,
        'name' => 'Yusuf Savran',
        'email' => 'yusuf@example.com',
        'message' => 'Graag een atelierbezoek.',
        'datum' => '2026-09-01',
        'moment' => 'Ochtend',
    ])->assertRedirect()
        ->assertCookie(InquiryDeviceCookie::NAME);

    $inquiry = Inquiry::query()->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->type)->toBe(InquiryType::Appointment)
        ->and($inquiry->user_id)->toBeNull()
        ->and($inquiry->name)->toBe('Yusuf Savran')
        ->and($inquiry->email)->toBe('yusuf@example.com')
        ->and($inquiry->subject)->toBe('Afspraak aanvraag')
        ->and($inquiry->message)->toBe('Graag een atelierbezoek.')
        ->and($inquiry->locale)->toBe('nl')
        ->and($inquiry->device_token)->not->toBeNull()
        ->and($inquiry->meta)->toMatchArray([
            'datum' => '2026-09-01',
            'moment' => 'Ochtend',
        ]);

    Mail::assertQueued(InquiryReceived::class, function (InquiryReceived $mail) use ($inquiry) {
        return $mail->inquiry->is($inquiry)
            && $mail->hasTo(config('mail.bureau_address', config('mail.from.address')));
    });

    Mail::assertQueued(InquiryConfirmation::class, function (InquiryConfirmation $mail) use ($inquiry) {
        return $mail->inquiry->is($inquiry)
            && $mail->hasTo('yusuf@example.com');
    });
});

test('a logged-in visitor is stored on the contact inquiry', function () {
    Mail::fake();

    $user = User::factory()->create([
        'name' => 'Marie Dupont',
        'email' => 'marie@example.com',
    ]);

    $this->actingAs($user)
        ->post('/en/contact', [
            'kind' => InquiryType::Feedback->value,
            'name' => 'Marie Dupont',
            'email' => 'marie@example.com',
            'message' => 'Beautiful site.',
            'ervaring' => 'Uitstekend',
        ])->assertRedirect();

    $inquiry = Inquiry::query()->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->type)->toBe(InquiryType::Feedback)
        ->and($inquiry->user_id)->toBe($user->id)
        ->and($inquiry->subject)->toBe('Feedback')
        ->and($inquiry->locale)->toBe('en');
});
