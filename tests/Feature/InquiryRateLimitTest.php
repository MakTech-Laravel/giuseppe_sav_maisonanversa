<?php

use App\Enums\InquiryType;
use App\Enums\RoleEnum;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\Inquiry\InquiryDeviceCookie;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
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

test('founding circle members submit priority contact inquiries', function () {
    Mail::fake();

    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);

    $member = User::factory()->create();
    $member->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $this->actingAs($member)
        ->post('/nl/contact', contactPayload())
        ->assertRedirect();

    $inquiry = Inquiry::query()->latest('id')->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->priority)->toBeTrue()
        ->and($inquiry->user_id)->toBe($member->id)
        ->and($inquiry->slaDueAt()?->equalTo($inquiry->created_at->copy()->addHours(Inquiry::SLA_HOURS)))->toBeTrue();
});

test('guest contact inquiries are not flagged as priority', function () {
    Mail::fake();

    $this->post('/nl/contact', contactPayload())->assertRedirect();

    expect(Inquiry::query()->latest('id')->first()?->priority)->toBeFalse();
});

test('unseen priority inquiries breach the sla after twelve hours', function () {
    $inquiry = Inquiry::factory()->priority()->create();

    expect($inquiry->isSlaBreached())->toBeFalse();

    $this->travel(13)->hours();

    expect($inquiry->fresh()->isSlaBreached())->toBeTrue();

    $inquiry->markSeen();

    expect($inquiry->fresh()->isSlaBreached())->toBeFalse();
});
