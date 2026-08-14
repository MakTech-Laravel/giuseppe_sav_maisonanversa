<?php

use App\Enums\SubscriberSource;
use App\Enums\SubscriberStatus;
use App\Jobs\SyncSubscriberToBrevo;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Queue;

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

    Queue::assertPushed(SyncSubscriberToBrevo::class);
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
