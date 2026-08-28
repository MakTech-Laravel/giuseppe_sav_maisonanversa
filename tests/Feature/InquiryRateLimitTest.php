<?php

use App\Enums\InquiryType;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\Inquiry\InquiryDeviceCookie;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

function contactPayload(array $overrides = []): array
{
    return [
        'kind' => InquiryType::Appointment->value,
        'name' => 'Yusuf Savran',
        'email' => 'yusuf@example.com',
        'message' => 'Graag een atelierbezoek.',
        ...$overrides,
    ];
}

test('a third appointment in 24 hours is rejected for the same ip', function () {
    Mail::fake();

    $this->post('/nl/contact', contactPayload())->assertRedirect();
    $this->post('/nl/contact', contactPayload())->assertRedirect();

    $this->post('/nl/contact', contactPayload())
        ->assertRedirect()
        ->assertSessionHasErrors('kind');

    expect(Inquiry::query()->where('type', InquiryType::Appointment)->count())->toBe(2);
});

test('a different form kind is still allowed after the appointment cap', function () {
    Mail::fake();

    $this->post('/nl/contact', contactPayload())->assertRedirect();
    $this->post('/nl/contact', contactPayload())->assertRedirect();

    $this->post('/nl/contact', contactPayload([
        'kind' => InquiryType::Feedback->value,
        'message' => 'Mooie site.',
    ]))->assertRedirect();

    expect(Inquiry::query()->where('type', InquiryType::Appointment)->count())->toBe(2)
        ->and(Inquiry::query()->where('type', InquiryType::Feedback)->count())->toBe(1);
});

test('the 24 hour cap follows the same device cookie across ip addresses', function () {
    Mail::fake();

    $token = (string) Str::uuid();

    $this->withCookie(InquiryDeviceCookie::NAME, $token)
        ->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->post('/nl/contact', contactPayload())
        ->assertRedirect();

    $this->withCookie(InquiryDeviceCookie::NAME, $token)
        ->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->post('/nl/contact', contactPayload())
        ->assertRedirect();

    $this->withCookie(InquiryDeviceCookie::NAME, $token)
        ->withServerVariables(['REMOTE_ADDR' => '10.0.0.8'])
        ->post('/nl/contact', contactPayload())
        ->assertSessionHasErrors('kind');
});

test('the 24 hour cap follows the same authenticated user across ip addresses', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '10.1.0.1'])
        ->post('/nl/contact', contactPayload())
        ->assertRedirect();

    $this->actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '10.1.0.2'])
        ->post('/nl/contact', contactPayload())
        ->assertRedirect();

    $this->actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '10.1.0.3'])
        ->post('/nl/contact', contactPayload())
        ->assertSessionHasErrors('kind');
});

test('club corner inquiries do not count toward the contact form cap', function () {
    Mail::fake();

    $this->post('/nl/contact', contactPayload())->assertRedirect();
    $this->post('/nl/contact', contactPayload())->assertRedirect();

    $this->post('/nl/corner/inquire', [
        'club_name' => 'Padel Antwerpen',
        'name' => 'Marie Dupont',
        'email' => 'marie@example.com',
        'location' => 'Antwerpen, België',
        'courts' => '4-6 courts',
        'format' => 'Formaat A — Heritage Corner',
        'message' => 'Wij zoeken een premium partnership.',
    ])->assertRedirect();

    expect(Inquiry::query()->where('type', InquiryType::Appointment)->count())->toBe(2)
        ->and(Inquiry::query()->where('type', InquiryType::Corner)->count())->toBe(1);

    $this->post('/nl/contact', contactPayload())
        ->assertSessionHasErrors('kind');
});
