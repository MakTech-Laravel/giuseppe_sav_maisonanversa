<?php

use App\Enums\RoleEnum;
use App\Enums\SubscriberSource;
use App\Enums\SubscriberStatus;
use App\Exports\NewsletterSubscribersExport;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can view letter subscribers with filters and pagination', function () {
    $this->actingAs($this->admin)
        ->get(localized('admin.letter.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/letter/index')
            ->has('subscribers.data')
            ->where('filters.search', '')
            ->where('filters.status', '')
            ->where('filters.source', '')
            ->where('filters.locale', '')
            ->where('filters.per_page', 15)
            ->has('perPageOptions', 5)
            ->has('letterConnected')
        );
});

test('staff see topic preferences on each subscriber row', function () {
    NewsletterSubscriber::factory()->create([
        'email' => 'topics@example.com',
        'name' => 'Topic Person',
        'preferences' => [
            'heritageLetter' => true,
            'productUpdates' => false,
            'events' => true,
        ],
    ]);

    $this->actingAs($this->admin)
        ->get(localized('admin.letter.index', ['search' => 'topics@']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('subscribers.data', 1)
            ->where('subscribers.data.0.email', 'topics@example.com')
            ->where('subscribers.data.0.preferences.heritageLetter', true)
            ->where('subscribers.data.0.preferences.productUpdates', false)
            ->where('subscribers.data.0.preferences.events', true)
        );
});

test('staff can paginate every subscriber without a 200 cap', function () {
    NewsletterSubscriber::factory()->count(21)->create();

    $this->actingAs($this->admin)
        ->get(localized('admin.letter.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/letter/index')
            ->has('subscribers.data', 15)
            ->where('subscribers.total', 21)
            ->where('subscribers.per_page', 15)
        );
});

test('staff can search letter subscribers by email and name', function () {
    NewsletterSubscriber::factory()->create([
        'email' => 'ada@example.com',
        'name' => 'Ada Lovelace',
    ]);
    NewsletterSubscriber::factory()->create([
        'email' => 'other@example.com',
        'name' => 'Other Person',
    ]);

    $this->actingAs($this->admin)
        ->get(localized('admin.letter.index', ['search' => 'ada@']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('subscribers.data', 1)
            ->where('subscribers.data.0.email', 'ada@example.com')
            ->where('filters.search', 'ada@')
        );

    $this->actingAs($this->admin)
        ->get(localized('admin.letter.index', ['search' => 'Lovelace']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('subscribers.data', 1)
            ->where('subscribers.data.0.name', 'Ada Lovelace')
        );
});

test('staff can filter letter subscribers by status source and locale', function () {
    NewsletterSubscriber::factory()->create([
        'email' => 'pending@example.com',
        'status' => SubscriberStatus::Pending,
        'source' => SubscriberSource::Home,
        'locale' => 'nl',
    ]);
    NewsletterSubscriber::factory()->create([
        'email' => 'waitlist@example.com',
        'status' => SubscriberStatus::Subscribed,
        'source' => SubscriberSource::Waitlist,
        'locale' => 'fr',
    ]);

    $this->actingAs($this->admin)
        ->get(localized('admin.letter.index', ['status' => 'pending']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('subscribers.data', 1)
            ->where('subscribers.data.0.email', 'pending@example.com')
            ->where('filters.status', 'pending')
        );

    $this->actingAs($this->admin)
        ->get(localized('admin.letter.index', ['source' => SubscriberSource::Waitlist->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('subscribers.data', 1)
            ->where('subscribers.data.0.email', 'waitlist@example.com')
            ->where('filters.source', 'waitlist')
        );

    $this->actingAs($this->admin)
        ->get(localized('admin.letter.index', ['subscriber_locale' => 'fr']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('subscribers.data', 1)
            ->where('subscribers.data.0.email', 'waitlist@example.com')
            ->where('filters.locale', 'fr')
        );
});

test('staff can paginate letter subscribers with a whitelisted per page value', function () {
    NewsletterSubscriber::factory()->count(12)->create();

    $this->actingAs($this->admin)
        ->get(localized('admin.letter.index', ['per_page' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.per_page', 10)
            ->where('subscribers.per_page', 10)
            ->has('subscribers.data', 10)
        );

    $this->actingAs($this->admin)
        ->get(localized('admin.letter.index', ['per_page' => 999]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.per_page', 15)
            ->where('subscribers.per_page', 15)
        );
});

test('staff can export filtered letter subscribers', function () {
    Excel::fake();

    NewsletterSubscriber::factory()->create([
        'email' => 'keep@example.com',
        'status' => SubscriberStatus::Subscribed,
    ]);
    NewsletterSubscriber::factory()->create([
        'email' => 'drop@example.com',
        'status' => SubscriberStatus::Unsubscribed,
    ]);

    $this->actingAs($this->admin)
        ->get(localized('admin.letter.export', ['status' => 'subscribed']))
        ->assertOk();

    Excel::assertDownloaded('heritage-letter.csv', function (NewsletterSubscribersExport $export): bool {
        $emails = $export->collection()->pluck('email');

        return $emails->contains('keep@example.com')
            && ! $emails->contains('drop@example.com')
            && $export->headings() === [
                'Email',
                'Name',
                'Locale',
                'Source',
                'Status',
                'Heritage Letter',
                'Product Updates',
                'Events',
                'Consent At',
                'Synced At',
            ];
    });
});

test('customers cannot view letter subscribers', function () {
    $user = User::factory()->customer()->create();

    $this->actingAs($user)
        ->get(localized('admin.letter.index'))
        ->assertForbidden();
});

test('guests are redirected away from letter subscribers', function () {
    $this->get(localized('admin.letter.index'))
        ->assertRedirect(localized('maison.home', absolute: false));
});
