<?php

use App\Enums\SubscriberSource;
use App\Enums\SubscriberStatus;
use App\Jobs\SyncSubscriberToBrevo;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('members can save letter preferences and join the list', function () {
    Queue::fake();

    $user = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'locale' => 'en',
    ]);

    $this->actingAs($user)
        ->from(localized('member.letter', absolute: false))
        ->patch(localized('member.letter.update'), [
            'heritageLetter' => true,
            'productUpdates' => false,
            'events' => true,
        ])
        ->assertRedirect(localized('member.letter', absolute: false));

    $subscriber = NewsletterSubscriber::query()->where('email', 'ada@example.com')->first();

    expect($subscriber)->not->toBeNull()
        ->and($subscriber->name)->toBe('Ada Lovelace')
        ->and($subscriber->locale)->toBe('en')
        ->and($subscriber->source)->toBe(SubscriberSource::Member)
        ->and($subscriber->status)->toBe(SubscriberStatus::Subscribed)
        ->and($subscriber->topicPreferences())->toBe([
            'heritageLetter' => true,
            'productUpdates' => false,
            'events' => true,
        ]);

    Queue::assertPushed(SyncSubscriberToBrevo::class, function (SyncSubscriberToBrevo $job) use ($subscriber): bool {
        return $job->subscriber->is($subscriber) && $job->sendWelcome === true;
    });
});

test('unchecking every topic unsubscribes the member', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'leave@example.com']);
    $subscriber = NewsletterSubscriber::factory()->create([
        'email' => 'leave@example.com',
        'status' => SubscriberStatus::Subscribed,
        'source' => SubscriberSource::Home,
    ]);

    $this->actingAs($user)
        ->from(localized('member.letter', absolute: false))
        ->patch(localized('member.letter.update'), [
            'heritageLetter' => false,
            'productUpdates' => false,
            'events' => false,
        ])
        ->assertRedirect();

    expect($subscriber->fresh()->status)->toBe(SubscriberStatus::Unsubscribed)
        ->and($subscriber->fresh()->source)->toBe(SubscriberSource::Home)
        ->and($subscriber->fresh()->topicPreferences())->toBe([
            'heritageLetter' => false,
            'productUpdates' => false,
            'events' => false,
        ]);

    Queue::assertPushed(SyncSubscriberToBrevo::class, function (SyncSubscriberToBrevo $job) use ($subscriber): bool {
        return $job->subscriber->is($subscriber) && $job->sendWelcome === false;
    });
});

test('saving all topics off does not create a subscriber row', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'never@example.com']);

    $this->actingAs($user)
        ->patch(localized('member.letter.update'), [
            'heritageLetter' => false,
            'productUpdates' => false,
            'events' => false,
        ])
        ->assertRedirect();

    expect(NewsletterSubscriber::query()->where('email', 'never@example.com')->exists())->toBeFalse();
    Queue::assertNothingPushed();
});

test('public join and member save share one subscriber row', function () {
    Queue::fake();

    $this->post(localized('maison.heritage-letter.store'), [
        'email' => 'shared@example.com',
        'name' => 'Public Name',
        'source' => SubscriberSource::Home->value,
    ])->assertRedirect();

    $user = User::factory()->create([
        'email' => 'shared@example.com',
        'name' => 'Member Name',
    ]);

    $this->actingAs($user)
        ->patch(localized('member.letter.update'), [
            'heritageLetter' => true,
            'productUpdates' => false,
            'events' => false,
        ])
        ->assertRedirect();

    $rows = NewsletterSubscriber::query()->where('email', 'shared@example.com')->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->source)->toBe(SubscriberSource::Home)
        ->and($rows->first()->status)->toBe(SubscriberStatus::Subscribed)
        ->and($rows->first()->topicPreferences()['productUpdates'])->toBeFalse();
});

test('later preference saves do not queue a welcome', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'again@example.com']);
    NewsletterSubscriber::factory()->create([
        'email' => 'again@example.com',
        'status' => SubscriberStatus::Subscribed,
    ]);

    $this->actingAs($user)
        ->patch(localized('member.letter.update'), [
            'heritageLetter' => true,
            'productUpdates' => true,
            'events' => false,
        ])
        ->assertRedirect();

    Queue::assertPushed(SyncSubscriberToBrevo::class, function (SyncSubscriberToBrevo $job): bool {
        return $job->sendWelcome === false;
    });
});

test('the member letter page exposes status and connection state', function () {
    $user = User::factory()->create(['email' => 'page@example.com']);
    NewsletterSubscriber::factory()->create([
        'email' => 'page@example.com',
        'status' => SubscriberStatus::Subscribed,
        'preferences' => [
            'heritageLetter' => true,
            'productUpdates' => false,
            'events' => true,
        ],
    ]);

    $this->actingAs($user)
        ->get(localized('member.letter'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/letter')
            ->where('status', 'subscribed')
            ->where('preferences.heritageLetter', true)
            ->where('preferences.events', true)
            ->missing('letterConnected')
        );
});

test('changing a member email rebinds the subscriber row', function () {
    Queue::fake();

    $user = User::factory()->create([
        'email' => 'old@example.com',
        'username' => 'letter_member',
    ]);
    NewsletterSubscriber::factory()->create([
        'email' => 'old@example.com',
        'status' => SubscriberStatus::Subscribed,
    ]);

    $this->actingAs($user)
        ->patch(localized('member.profile.update'), [
            'name' => $user->name,
            'email' => 'new@example.com',
            'username' => 'letter_member',
        ])
        ->assertRedirect(localized('member.profile', absolute: false));

    expect(NewsletterSubscriber::query()->where('email', 'old@example.com')->exists())->toBeFalse();

    $subscriber = NewsletterSubscriber::query()->where('email', 'new@example.com')->first();

    expect($subscriber)->not->toBeNull()
        ->and($subscriber->status)->toBe(SubscriberStatus::Subscribed);

    Queue::assertPushed(SyncSubscriberToBrevo::class, function (SyncSubscriberToBrevo $job) use ($subscriber): bool {
        return $job->subscriber->is($subscriber)
            && $job->removeEmail === 'old@example.com'
            && $job->sendWelcome === false;
    });
});

test('changing to an email that already has a subscriber merges the rows', function () {
    Queue::fake();

    $user = User::factory()->create([
        'email' => 'from@example.com',
        'username' => 'merge_member',
    ]);
    NewsletterSubscriber::factory()->create([
        'email' => 'from@example.com',
        'status' => SubscriberStatus::Subscribed,
        'preferences' => [
            'heritageLetter' => true,
            'productUpdates' => false,
            'events' => false,
        ],
    ]);
    NewsletterSubscriber::factory()->create([
        'email' => 'to@example.com',
        'status' => SubscriberStatus::Unsubscribed,
        'preferences' => [
            'heritageLetter' => false,
            'productUpdates' => true,
            'events' => false,
        ],
    ]);

    $this->actingAs($user)
        ->patch(localized('member.profile.update'), [
            'name' => $user->name,
            'email' => 'to@example.com',
            'username' => 'merge_member',
        ])
        ->assertRedirect(localized('member.profile', absolute: false));

    expect(NewsletterSubscriber::query()->where('email', 'from@example.com')->exists())->toBeFalse()
        ->and(NewsletterSubscriber::query()->where('email', 'to@example.com')->count())->toBe(1);

    $survivor = NewsletterSubscriber::query()->where('email', 'to@example.com')->first();

    expect($survivor->status)->toBe(SubscriberStatus::Subscribed)
        ->and($survivor->topicPreferences())->toBe([
            'heritageLetter' => true,
            'productUpdates' => true,
            'events' => false,
        ]);
});
