<?php

use App\Enums\InquiryType;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Mail;

test('the club corner page renders the maison corner component', function () {
    $this->get('/nl/corner')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('maison/corner'));
});

test('the partnership form uses inertia useForm instead of onclick handlers', function () {
    $source = file_get_contents(resource_path('js/pages/maison/corner.tsx'));

    expect($source)
        ->toContain('onSubmit={onSubmit}')
        ->toContain('useRef')
        ->toContain('useForm')
        ->toContain("from '@/routes/maison/corner'")
        ->toContain("from 'react'")
        ->not->toContain('onclick=')
        ->not->toContain('useState');
});

test('the partnership form includes the expected club fields', function () {
    $source = file_get_contents(resource_path('js/pages/maison/corner.tsx'));

    expect($source)
        ->toContain('name="club_name"')
        ->toContain('name="name"')
        ->toContain('name="email"')
        ->toContain('name="location"')
        ->toContain('name="courts"')
        ->toContain('name="format"')
        ->toContain('name="message"');
});

test('a club corner inquiry is persisted and queued to the bureau inbox', function () {
    Mail::fake();

    $this->post('/nl/corner/inquire', [
        'club_name' => 'Padel Antwerpen',
        'name' => 'Marie Dupont',
        'email' => 'marie@example.com',
        'location' => 'Antwerpen, België',
        'courts' => '4-6 courts',
        'format' => 'Formaat A — Heritage Corner',
        'message' => 'Wij zoeken een premium partnership.',
    ])->assertRedirect();

    $inquiry = Inquiry::query()->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->type)->toBe(InquiryType::Corner)
        ->and($inquiry->name)->toBe('Marie Dupont')
        ->and($inquiry->email)->toBe('marie@example.com')
        ->and($inquiry->subject)->toBe('Club Corner partnership')
        ->and($inquiry->message)->toBe('Wij zoeken een premium partnership.')
        ->and($inquiry->locale)->toBe('nl')
        ->and($inquiry->meta)->toMatchArray([
            'club_name' => 'Padel Antwerpen',
            'location' => 'Antwerpen, België',
            'courts' => '4-6 courts',
            'format' => 'Formaat A — Heritage Corner',
        ]);

    Mail::assertQueued(InquiryReceived::class, function (InquiryReceived $mail) use ($inquiry) {
        return $mail->inquiry->is($inquiry)
            && $mail->hasTo(config('mail.bureau_address', config('mail.from.address')));
    });
});
