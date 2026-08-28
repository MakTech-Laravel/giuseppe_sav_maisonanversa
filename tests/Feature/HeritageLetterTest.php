<?php

use App\Contracts\BrevoContacts;
use App\Enums\SubscriberSource;
use App\Enums\SubscriberStatus;
use App\Jobs\SyncSubscriberToBrevo;
use App\Mail\HeritageLetterWelcome;
use App\Models\NewsletterSubscriber;
use App\Services\Brevo\HttpBrevoContacts;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

test('heritage letter signup stores consent and queues a brevo sync', function () {
    Queue::fake();

    $this->post(localized('maison.heritage-letter.store'), [
        'email' => 'letter@example.com',
        'name' => 'Ada',
        'source' => SubscriberSource::Home->value,
    ])->assertRedirect();

    $subscriber = NewsletterSubscriber::query()->where('email', 'letter@example.com')->first();

    expect($subscriber)->not->toBeNull()
        ->and($subscriber->locale)->toBe('nl')
        ->and($subscriber->status)->toBe(SubscriberStatus::Subscribed)
        ->and($subscriber->consent_at)->not->toBeNull();

    Queue::assertPushed(SyncSubscriberToBrevo::class, function (SyncSubscriberToBrevo $job) use ($subscriber): bool {
        return $job->subscriber->is($subscriber) && $job->sendWelcome === true;
    });
});

test('heritage letter signup is idempotent for the same email', function () {
    Queue::fake();

    $payload = [
        'email' => 'repeat@example.com',
        'source' => SubscriberSource::Modal->value,
    ];

    $this->post(localized('maison.heritage-letter.store'), $payload)->assertRedirect();
    $this->post(localized('maison.heritage-letter.store'), $payload)->assertRedirect();

    expect(NewsletterSubscriber::query()->where('email', 'repeat@example.com')->count())->toBe(1);
});

test('honeypot fields are rejected', function () {
    $this->post(localized('maison.heritage-letter.store'), [
        'email' => 'bot@example.com',
        'source' => SubscriberSource::Home->value,
        'website' => 'https://spam.test',
    ])->assertSessionHasErrors('website');
});

test('a second public signup does not queue another welcome', function () {
    Queue::fake();

    $payload = [
        'email' => 'repeat-welcome@example.com',
        'source' => SubscriberSource::Home->value,
    ];

    $this->post(localized('maison.heritage-letter.store'), $payload)->assertRedirect();
    $this->post(localized('maison.heritage-letter.store'), $payload)->assertRedirect();

    $jobs = Queue::pushed(SyncSubscriberToBrevo::class);

    expect($jobs)->toHaveCount(2)
        ->and($jobs[0]->sendWelcome)->toBeTrue()
        ->and($jobs[1]->sendWelcome)->toBeFalse();
});

test('signed unsubscribe marks the subscriber and queues a brevo removal', function () {
    Queue::fake();

    $subscriber = NewsletterSubscriber::factory()->create([
        'status' => SubscriberStatus::Subscribed,
    ]);

    $this->get(URL::signedRoute('maison.heritage-letter.unsubscribe', [
        'locale' => defaultLocale(),
        'subscriber' => $subscriber,
    ]))->assertOk();

    expect($subscriber->fresh()->status)->toBe(SubscriberStatus::Unsubscribed)
        ->and($subscriber->fresh()->topicPreferences())->toBe([
            'heritageLetter' => false,
            'productUpdates' => false,
            'events' => false,
        ]);

    Queue::assertPushed(SyncSubscriberToBrevo::class, function (SyncSubscriberToBrevo $job) use ($subscriber): bool {
        return $job->subscriber->is($subscriber) && $job->sendWelcome === false;
    });
});

test('unsigned unsubscribe urls are forbidden', function () {
    $subscriber = NewsletterSubscriber::factory()->create();

    $this->get(localized('maison.heritage-letter.unsubscribe', [
        'subscriber' => $subscriber->id,
    ]))->assertForbidden();
});

test('the sync job upserts topic attributes and welcomes only on first subscribe', function () {
    Mail::fake();
    Http::preventStrayRequests();
    Http::fake([
        'https://api.brevo.com/v3/contacts' => Http::response(['id' => 99]),
    ]);

    config([
        'services.brevo.api_key' => 'test-key',
        'services.brevo.list_heritage_letter' => 12,
        'services.brevo.welcome_via' => 'local',
    ]);

    $subscriber = NewsletterSubscriber::factory()->create([
        'preferences' => [
            'heritageLetter' => true,
            'productUpdates' => false,
            'events' => true,
        ],
    ]);

    (new SyncSubscriberToBrevo($subscriber, sendWelcome: true))->handle(new HttpBrevoContacts);

    Http::assertSent(function (Request $request) use ($subscriber): bool {
        $payload = $request->data();

        return $request->url() === 'https://api.brevo.com/v3/contacts'
            && $payload['email'] === $subscriber->email
            && $payload['attributes']['HERITAGE_LETTER'] === true
            && $payload['attributes']['PRODUCT_UPDATES'] === false
            && $payload['attributes']['EVENTS'] === true;
    });

    Mail::assertQueued(HeritageLetterWelcome::class, function (HeritageLetterWelcome $mail) use ($subscriber): bool {
        return $mail->subscriber->is($subscriber);
    });

    (new SyncSubscriberToBrevo($subscriber->fresh(), sendWelcome: false))->handle(new HttpBrevoContacts);

    Mail::assertQueued(HeritageLetterWelcome::class, 1);
});

test('the sync job removes unsubscribed contacts from brevo', function () {
    $subscriber = NewsletterSubscriber::factory()->create([
        'status' => SubscriberStatus::Unsubscribed,
    ]);

    $brevo = Mockery::mock(BrevoContacts::class);
    $brevo->shouldReceive('unsubscribeHeritageLetterContact')
        ->once()
        ->with(Mockery::on(fn (NewsletterSubscriber $contact): bool => $contact->is($subscriber)));
    $brevo->shouldNotReceive('upsertHeritageLetterContact');

    (new SyncSubscriberToBrevo($subscriber))->handle($brevo);

    expect($subscriber->fresh()->status)->toBe(SubscriberStatus::Unsubscribed)
        ->and($subscriber->fresh()->synced_at)->not->toBeNull();
});

test('the welcome mail includes a signed unsubscribe url', function () {
    $subscriber = NewsletterSubscriber::factory()->create();

    $html = (new HeritageLetterWelcome($subscriber))->render();

    expect($html)
        ->toContain('heritage-letter/unsubscribe')
        ->toContain('signature=');
});
