<?php

use App\Enums\RoleEnum;
use App\Jobs\TranslateModelJob;
use App\Models\CommunityEvent;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can create a community event', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.events.store', ['locale' => 'nl']), [
            'title' => 'Club Night',
            'description' => 'An evening with the circle.',
            'starts_at' => now()->addWeek()->toDateTimeString(),
            'location' => 'Antwerp',
            'capacity' => 30,
        ])
        ->assertRedirect();

    expect(CommunityEvent::query()->where('title', 'Club Night')->exists())->toBeTrue();
});

test('staff can open the event edit page', function () {
    $event = CommunityEvent::factory()->create([
        'title' => 'Editable Event',
        'starts_at' => now()->addDays(4),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.events.edit', ['locale' => 'en', 'event' => $event->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/edit')
            ->where('source.title', 'Editable Event')
            ->where('defaultLocale', 'nl')
            ->has('translations.en')
            ->has('event.starts_at')
            ->missing('event.title')
        );
});

test('event edit page shows stored english copy when admin locale is en', function () {
    $event = CommunityEvent::factory()->create([
        'title' => 'Bron titel',
        'description' => 'Bron beschrijving',
        'location' => 'Antwerpen',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.events.translations.update', ['locale' => 'nl', 'event' => $event->id]), [
            'target_locale' => 'en',
            'title' => 'English title',
            'description' => 'English description',
            'location' => 'Antwerp',
        ])
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->get(route('admin.events.edit', ['locale' => 'en', 'event' => $event->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/edit')
            ->where('translations.en.title', 'English title')
            ->where('source.title', 'Bron titel')
        );
});

test('staff can update a community event', function () {
    $event = CommunityEvent::factory()->create(['title' => 'Old Title']);

    $this->actingAs($this->admin)
        ->put(route('admin.events.update', ['locale' => 'nl', 'event' => $event->id]), [
            'title' => 'New Title',
            'description' => 'Updated description',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'location' => 'Brussels',
            'capacity' => 12,
        ])
        ->assertRedirect();

    expect($event->fresh()->title)->toBe('New Title')
        ->and($event->fresh()->location)->toBe('Brussels');
});

test('nl update with changed source text wipes translations and queues deepl', function () {
    $event = CommunityEvent::factory()->create([
        'title' => 'Original title',
        'description' => 'Original description',
        'location' => 'Antwerpen',
    ]);

    $event->translations()->create([
        'locale' => 'en',
        'column' => 'title',
        'value' => 'Stored EN title',
        'source_hash' => $event->translationSourceHash('title'),
    ]);

    Queue::fake();

    $this->actingAs($this->admin)
        ->put(route('admin.events.update', ['locale' => 'nl', 'event' => $event->id]), [
            'title' => 'Changed title',
            'description' => 'Original description',
            'starts_at' => $event->starts_at->toDateTimeString(),
            'location' => 'Antwerpen',
            'capacity' => $event->capacity,
        ])
        ->assertRedirect();

    expect($event->fresh()->title)->toBe('Changed title')
        ->and($event->fresh()->translations()->count())->toBe(0);

    Queue::assertPushed(TranslateModelJob::class, function (TranslateModelJob $job) use ($event): bool {
        return $job->uniqueId() === CommunityEvent::class.':'.$event->id;
    });
});

test('nl update without source text change keeps existing translations', function () {
    $event = CommunityEvent::factory()->create([
        'title' => 'Stable title',
        'description' => 'Stable description',
        'location' => 'Antwerpen',
    ]);

    $event->translations()->delete();
    $event->translations()->create([
        'locale' => 'en',
        'column' => 'title',
        'value' => 'Stored EN title',
        'source_hash' => $event->translationSourceHash('title'),
    ]);

    Queue::fake();

    $this->actingAs($this->admin)
        ->put(route('admin.events.update', ['locale' => 'nl', 'event' => $event->id]), [
            'title' => 'Stable title',
            'description' => 'Stable description',
            'starts_at' => $event->starts_at->toDateTimeString(),
            'location' => 'Antwerpen',
            'capacity' => 25,
        ])
        ->assertRedirect();

    expect($event->fresh()->capacity)->toBe(25)
        ->and($event->fresh()->translations()->count())->toBe(1);

    Queue::assertNothingPushed();
});

test('staff can update event copy in english via regular edit', function () {
    $event = CommunityEvent::factory()->create([
        'title' => 'Bron titel',
        'description' => 'Bron beschrijving',
        'location' => 'Antwerpen',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.events.update', ['locale' => 'en', 'event' => $event->id]), [
            'title' => 'Edited EN title',
            'description' => 'Edited EN description',
            'starts_at' => $event->starts_at->toDateTimeString(),
            'location' => 'Antwerp',
            'capacity' => $event->capacity,
        ])
        ->assertRedirect();

    $event->refresh();

    expect($event->title)->toBe('Bron titel')
        ->and($event->translated('title', 'en'))->toBe('Edited EN title')
        ->and($event->translated('location', 'en'))->toBe('Antwerp');
});

test('staff can delete a community event', function () {
    $event = CommunityEvent::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.events.destroy', ['locale' => 'nl', 'event' => $event->id]))
        ->assertRedirect(route('admin.events.index', ['locale' => 'nl']));

    expect(CommunityEvent::query()->whereKey($event->id)->exists())->toBeFalse();
});

test('events index exposes starts_at capacity rsvp_count status and filters', function () {
    CommunityEvent::factory()->create([
        'title' => 'Heritage Meetup',
        'capacity' => 20,
        'starts_at' => now()->addDays(3),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.events.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/index')
            ->has('events.data', 1)
            ->where('events.data.0.title', 'Heritage Meetup')
            ->where('events.data.0.capacity', 20)
            ->where('events.data.0.rsvp_count', 0)
            ->where('events.data.0.status', 'opening')
            ->has('events.data.0.starts_at')
            ->has('events.data.0.location')
            ->has('events.data.0.thumbnail_url')
            ->where('filters.search', '')
            ->where('filters.status', '')
        );
});

test('events index can search by title', function () {
    CommunityEvent::factory()->create([
        'title' => 'Circle Night',
        'starts_at' => now()->addWeek(),
    ]);
    CommunityEvent::factory()->create([
        'title' => 'Studio Session',
        'starts_at' => now()->addDays(2),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.events.index', ['locale' => 'nl', 'search' => 'Circle']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/index')
            ->has('events.data', 1)
            ->where('events.data.0.title', 'Circle Night')
            ->where('filters.search', 'Circle')
        );
});

test('events index filters by opening ongoing and closed status', function () {
    CommunityEvent::factory()->opening()->create([
        'title' => 'Upcoming Event',
    ]);
    CommunityEvent::factory()->ongoing()->create([
        'title' => 'Ongoing Event',
    ]);
    CommunityEvent::factory()->closed()->create([
        'title' => 'Closed Event',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.events.index', ['locale' => 'nl', 'status' => 'opening']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.title', 'Upcoming Event')
            ->where('events.data.0.status', 'opening')
            ->where('filters.status', 'opening')
        );

    $this->actingAs($this->admin)
        ->get(route('admin.events.index', ['locale' => 'nl', 'status' => 'ongoing']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.title', 'Ongoing Event')
            ->where('events.data.0.status', 'ongoing')
            ->where('filters.status', 'ongoing')
        );

    $this->actingAs($this->admin)
        ->get(route('admin.events.index', ['locale' => 'nl', 'status' => 'closed']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.title', 'Closed Event')
            ->where('events.data.0.status', 'closed')
            ->where('filters.status', 'closed')
        );
});

test('admin can update event status', function () {
    $event = CommunityEvent::factory()->opening()->create([
        'title' => 'Status Toggle Event',
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.events.status', ['locale' => 'nl', 'event' => $event->id]), [
            'status' => 'ongoing',
        ])
        ->assertRedirect();

    expect($event->fresh()->status->value)->toBe('ongoing');

    $this->actingAs($this->admin)
        ->get(route('admin.events.index', ['locale' => 'nl', 'status' => 'ongoing']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.title', 'Status Toggle Event')
            ->where('events.data.0.status', 'ongoing')
        );
});

test('event show exposes translation bundle and status', function () {
    $event = CommunityEvent::factory()->create([
        'title' => 'Vertaal Event',
        'description' => 'Beschrijving',
        'location' => 'Antwerpen',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.events.show', ['locale' => 'nl', 'event' => $event->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/show')
            ->has('locales', 3)
            ->where('defaultLocale', 'nl')
            ->has('translations.nl')
            ->has('translations.en')
            ->has('translations.fr')
            ->where('translations.nl.title', 'Vertaal Event')
            ->has('translationStatus.en')
            ->has('translationStatus.fr')
        );
});

test('staff can manually update event translations for one locale only', function () {
    $event = CommunityEvent::factory()->create([
        'title' => 'Bron titel',
        'description' => 'Bron beschrijving',
        'location' => 'Antwerpen',
    ]);

    $event->translations()->create([
        'locale' => 'fr',
        'column' => 'title',
        'value' => 'Existing FR title',
        'source_hash' => $event->translationSourceHash('title'),
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.events.translations.update', ['locale' => 'nl', 'event' => $event->id]), [
            'target_locale' => 'en',
            'title' => 'Custom EN title',
            'description' => 'Custom EN description',
            'location' => 'Antwerp',
        ])
        ->assertRedirect(route('admin.events.show', ['locale' => 'nl', 'event' => $event->id]));

    $event->refresh();

    expect($event->translated('title', 'en'))->toBe('Custom EN title')
        ->and($event->translated('location', 'en'))->toBe('Antwerp')
        ->and($event->translated('title', 'fr'))->toBe('Existing FR title');
});

test('translate column runs deepl synchronously and stores result', function () {
    fakeDeepLTranslations();

    $event = CommunityEvent::factory()->create([
        'title' => 'Tijger titel',
        'description' => 'Beschrijving',
        'location' => 'Gent',
    ]);

    $event->translations()->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.events.translate-column', ['locale' => 'nl', 'event' => $event->id]), [
            'target_locale' => 'en',
            'column' => 'title',
        ])
        ->assertRedirect();

    expect($event->fresh()->translated('title', 'en'))->toBe('EN Tijger titel');
});

test('translate column without deepl key returns error flash', function () {
    config(['services.deepl.key' => null]);

    $event = CommunityEvent::factory()->create([
        'title' => 'Titel zonder deepl',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.events.translate-column', ['locale' => 'nl', 'event' => $event->id]), [
            'target_locale' => 'en',
            'column' => 'title',
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'error');
});

test('staff can queue deepl retranslation for an event', function () {
    fakeDeepLTranslations();

    $event = CommunityEvent::factory()->create([
        'title' => 'Hervertaal titel',
        'description' => 'Hervertaal beschrijving',
        'location' => 'Gent',
    ]);

    $event->translations()->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.events.translate', ['locale' => 'nl', 'event' => $event->id]))
        ->assertRedirect(route('admin.events.show', ['locale' => 'nl', 'event' => $event->id]));

    expect($event->fresh()->translations()->count())->toBe(6);
});
