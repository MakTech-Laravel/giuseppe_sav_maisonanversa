<?php

use App\Enums\RoleEnum;
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

test('creating an event stores deepl translations for all locales', function () {
    fakeDeepLTranslations();

    $this->actingAs($this->admin)
        ->post(route('admin.events.store', ['locale' => 'nl']), [
            'title' => 'DeepL Event titel',
            'description' => 'DeepL Event beschrijving.',
            'starts_at' => now()->addWeek()->toDateTimeString(),
            'location' => 'Antwerpen',
            'capacity' => 30,
        ])
        ->assertRedirect();

    $event = CommunityEvent::query()->where('title', 'DeepL Event titel')->firstOrFail();

    expect($event->translations()->count())->toBe(9)
        ->and($event->translated('title', 'nl'))->toBe('NL DeepL Event titel')
        ->and($event->translated('title', 'en'))->toBe('EN DeepL Event titel')
        ->and($event->translated('location', 'fr'))->toBe('FR Antwerpen');
});

test('staff can open the event edit page with source fields', function () {
    $event = CommunityEvent::factory()->create([
        'title' => 'Editable Event',
        'starts_at' => now()->addDays(4),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.events.edit', ['locale' => 'en', 'event' => $event->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/edit')
            ->where('event.title', 'Editable Event')
            ->where('event.id', (string) $event->id)
            ->has('event.starts_at')
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

test('editing event source requeues deepl for all locales', function () {
    fakeDeepLTranslations();

    $event = CommunityEvent::factory()->create([
        'title' => 'Original source title',
        'description' => 'Original source description',
        'location' => 'Antwerpen',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.events.update', ['locale' => 'nl', 'event' => $event->id]), [
            'title' => 'Updated source title',
            'description' => 'Updated source description',
            'starts_at' => $event->starts_at->toDateTimeString(),
            'location' => 'Gent',
            'capacity' => $event->capacity,
        ])
        ->assertRedirect(route('admin.events.show', ['locale' => 'nl', 'event' => $event->id]));

    $event->refresh();

    expect($event->title)->toBe('Updated source title')
        ->and($event->translated('title', 'en'))->toBe('EN Updated source title')
        ->and($event->translated('location', 'fr'))->toBe('FR Gent');
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

test('event show exposes translation bundle and status for all locales', function () {
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
            ->has('translations.nl')
            ->has('translations.en')
            ->has('translations.fr')
            ->where('translations.nl.title', 'Vertaal Event')
            ->has('translationStatus.nl')
            ->has('translationStatus.en')
            ->has('translationStatus.fr')
        );
});

test('event detail page shows translated content for the active locale', function () {
    $event = CommunityEvent::factory()->create([
        'title' => 'English source title',
        'description' => 'English source description',
        'location' => 'Antwerp',
    ]);

    $event->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'title'],
        ['value' => 'Titre source en français', 'source_hash' => $event->translationSourceHash('title')],
    );
    $event->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'description'],
        ['value' => 'Description source en français.', 'source_hash' => $event->translationSourceHash('description')],
    );
    $event->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'location'],
        ['value' => 'Anvers', 'source_hash' => $event->translationSourceHash('location')],
    );

    $this->actingAs($this->admin)
        ->get(route('admin.events.show', ['locale' => 'fr', 'event' => $event->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/show')
            ->where('event.title', 'Titre source en français')
            ->where('event.description', 'Description source en français.')
            ->where('event.location', 'Anvers')
        );
});

test('staff can manually update event translations for all locales', function () {
    $event = CommunityEvent::factory()->create([
        'title' => 'Bron titel',
        'description' => 'Bron beschrijving',
        'location' => 'Antwerpen',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.events.translations.update', ['locale' => 'nl', 'event' => $event->id]), [
            'nl' => [
                'title' => 'Custom NL title',
                'description' => 'Custom NL description',
                'location' => 'Antwerpen NL',
            ],
            'en' => [
                'title' => 'Custom EN title',
                'description' => 'Custom EN description',
                'location' => 'Antwerp',
            ],
            'fr' => [
                'title' => 'Custom FR title',
                'description' => 'Custom FR description',
                'location' => 'Anvers',
            ],
        ])
        ->assertRedirect(route('admin.events.show', ['locale' => 'nl', 'event' => $event->id]));

    $event->refresh();

    expect($event->title)->toBe('Bron titel')
        ->and($event->translated('title', 'nl'))->toBe('Custom NL title')
        ->and($event->translated('title', 'en'))->toBe('Custom EN title')
        ->and($event->translated('location', 'fr'))->toBe('Anvers');
});

test('staff can retranslate a single event locale from source', function () {
    fakeDeepLTranslations();

    $event = CommunityEvent::factory()->create([
        'title' => 'Single locale source title',
        'description' => 'Single locale source description',
        'location' => 'Gent',
    ]);

    $event->translations()->updateOrCreate(
        ['locale' => 'en', 'column' => 'title'],
        ['value' => 'Keep EN title', 'source_hash' => $event->translationSourceHash('title')],
    );
    $event->translations()->updateOrCreate(
        ['locale' => 'en', 'column' => 'description'],
        ['value' => 'Keep EN description', 'source_hash' => $event->translationSourceHash('description')],
    );
    $event->translations()->updateOrCreate(
        ['locale' => 'en', 'column' => 'location'],
        ['value' => 'Keep EN location', 'source_hash' => $event->translationSourceHash('location')],
    );
    $event->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'title'],
        ['value' => 'Keep FR title', 'source_hash' => $event->translationSourceHash('title')],
    );
    $event->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'description'],
        ['value' => 'Keep FR description', 'source_hash' => $event->translationSourceHash('description')],
    );
    $event->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'location'],
        ['value' => 'Keep FR location', 'source_hash' => $event->translationSourceHash('location')],
    );

    $this->actingAs($this->admin)
        ->post(route('admin.events.translate', ['locale' => 'nl', 'event' => $event->id]), [
            'target_locale' => 'nl',
        ])
        ->assertRedirect(route('admin.events.show', ['locale' => 'nl', 'event' => $event->id]));

    $event->refresh();

    expect($event->translated('title', 'nl'))->toBe('NL Single locale source title')
        ->and($event->translated('description', 'nl'))->toBe('NL Single locale source description')
        ->and($event->translated('location', 'nl'))->toBe('NL Gent')
        ->and($event->translated('title', 'en'))->toBe('Keep EN title')
        ->and($event->translated('title', 'fr'))->toBe('Keep FR title');
});

test('staff can queue deepl retranslation for all event locales', function () {
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

    expect($event->fresh()->translations()->count())->toBe(9);
});
