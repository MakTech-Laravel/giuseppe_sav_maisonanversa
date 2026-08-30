<?php

use App\Enums\SubscriberSource;
use App\Enums\SubscriberStatus;
use App\Enums\UserGender;
use App\Jobs\SyncSubscriberToBrevo;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('members can save letter preferences and join the list', function () {
    Queue::fake();

    $user = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'locale' => 'en',
    ]);

    $this->actingAs($user)
        ->from(localized('member.email-preferences', absolute: false))
        ->patch(localized('member.email-preferences.update'), [
            'heritageLetter' => true,
            'productUpdates' => false,
            'events' => true,
        ])
        ->assertRedirect(localized('member.email-preferences', absolute: false));

    $subscriber = NewsletterSubscriber::query()->where('email', 'ada@example.com')->first();

    expect($subscriber)->not->toBeNull()
        ->and($subscriber->user_id)->toBe($user->id)
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
        ->from(localized('member.email-preferences', absolute: false))
        ->patch(localized('member.email-preferences.update'), [
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
        ->patch(localized('member.email-preferences.update'), [
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
        ->patch(localized('member.email-preferences.update'), [
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
        ->patch(localized('member.email-preferences.update'), [
            'heritageLetter' => true,
            'productUpdates' => true,
            'events' => false,
        ])
        ->assertRedirect();

    Queue::assertPushed(SyncSubscriberToBrevo::class, function (SyncSubscriberToBrevo $job): bool {
        return $job->sendWelcome === false;
    });
});

test('the member letter page lists linked subscriptions', function () {
    $user = User::factory()->create(['email' => 'page@example.com']);
    NewsletterSubscriber::factory()->create([
        'email' => 'page@example.com',
        'status' => SubscriberStatus::Subscribed,
        'source' => SubscriberSource::Home,
        'user_id' => $user->id,
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
            ->has('subscriptions', 1)
            ->where('subscriptions.0.email', 'page@example.com')
            ->where('subscriptions.0.status', 'subscribed')
            ->where('subscriptions.0.source', 'home')
            ->where('subscriptions.0.preferences.heritageLetter', true)
            ->where('subscriptions.0.preferences.events', true)
            ->missing('preferences')
            ->missing('letterConnected')
        );
});

test('the email preferences page exposes status and topics for the account email', function () {
    $user = User::factory()->create(['email' => 'prefs@example.com']);
    NewsletterSubscriber::factory()->create([
        'email' => 'prefs@example.com',
        'status' => SubscriberStatus::Subscribed,
        'preferences' => [
            'heritageLetter' => true,
            'productUpdates' => false,
            'events' => true,
        ],
    ]);

    $this->actingAs($user)
        ->get(localized('member.email-preferences'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/email-preferences')
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
            'gender' => $user->gender->value,
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
            'gender' => $user->gender->value,
        ])
        ->assertRedirect(localized('member.profile', absolute: false));

    expect(NewsletterSubscriber::query()->where('email', 'from@example.com')->exists())->toBeFalse()
        ->and(NewsletterSubscriber::query()->where('email', 'to@example.com')->count())->toBe(1);

    $survivor = NewsletterSubscriber::query()->where('email', 'to@example.com')->first();

    expect($survivor->status)->toBe(SubscriberStatus::Subscribed)
        ->and($survivor->user_id)->toBe($user->id)
        ->and($survivor->topicPreferences())->toBe([
            'heritageLetter' => true,
            'productUpdates' => true,
            'events' => false,
        ]);
});

test('registering claims a guest heritage letter row for the same email', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    Queue::fake();

    $this->post(localized('maison.heritage-letter.store'), [
        'email' => 'ada@example.com',
        'name' => 'Ada',
        'source' => SubscriberSource::Home->value,
    ])->assertRedirect();

    $this->post(route('register.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'gender' => UserGender::Female->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $user = User::query()->where('email', 'ada@example.com')->first();
    $subscriber = NewsletterSubscriber::query()->where('email', 'ada@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($subscriber)->not->toBeNull()
        ->and($subscriber->user_id)->toBe($user->id);

    $user->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($user)
        ->get(localized('member.letter'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/letter')
            ->has('subscriptions', 1)
            ->where('subscriptions.0.email', 'ada@example.com')
        );
});

test('login claims an unowned heritage letter row for the account email', function () {
    Queue::fake();

    $this->post(localized('maison.heritage-letter.store'), [
        'email' => 'login@example.com',
        'source' => SubscriberSource::Home->value,
    ])->assertRedirect();

    $user = User::factory()->create(['email' => 'login@example.com']);

    expect(NewsletterSubscriber::query()->where('email', 'login@example.com')->value('user_id'))->toBeNull();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    expect(NewsletterSubscriber::query()->where('email', 'login@example.com')->value('user_id'))->toBe($user->id);
});

test('a logged-in member can add a second email from the public form', function () {
    Queue::fake();

    $user = User::factory()->create(['email' => 'member@example.com']);
    NewsletterSubscriber::factory()->create([
        'email' => 'member@example.com',
        'user_id' => $user->id,
        'source' => SubscriberSource::Member,
    ]);

    $this->actingAs($user)
        ->post(localized('maison.heritage-letter.store'), [
            'email' => 'other@example.com',
            'source' => SubscriberSource::Modal->value,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->get(localized('member.letter'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/letter')
            ->has('subscriptions', 2)
        );

    expect(NewsletterSubscriber::query()->where('email', 'other@example.com')->value('user_id'))->toBe($user->id);
});

test('heritage letter history does not include another members row', function () {
    $member = User::factory()->create(['email' => 'mine@example.com']);
    $other = User::factory()->create(['email' => 'theirs@example.com']);

    NewsletterSubscriber::factory()->create([
        'email' => 'mine@example.com',
        'user_id' => $member->id,
    ]);
    NewsletterSubscriber::factory()->create([
        'email' => 'theirs@example.com',
        'user_id' => $other->id,
    ]);
    NewsletterSubscriber::factory()->create([
        'email' => 'secret@example.com',
        'user_id' => $other->id,
    ]);

    $this->actingAs($member)
        ->get(localized('member.letter'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/letter')
            ->has('subscriptions', 1)
            ->where('subscriptions.0.email', 'mine@example.com')
        );
});

test('a logged-in member does not steal another members subscriber row', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create();
    $row = NewsletterSubscriber::factory()->create([
        'email' => 'owned@example.com',
        'user_id' => $owner->id,
    ]);

    $this->actingAs($member)
        ->post(localized('maison.heritage-letter.store'), [
            'email' => 'owned@example.com',
            'source' => SubscriberSource::Home->value,
        ])
        ->assertRedirect();

    expect($row->fresh()->user_id)->toBe($owner->id);

    $this->actingAs($member)
        ->get(localized('member.letter'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/letter')
            ->has('subscriptions', 0)
        );
});
