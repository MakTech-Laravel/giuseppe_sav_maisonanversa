<?php

use App\Enums\RoleEnum;
use App\Models\CommunityEvent;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
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
            ->where('event.id', (string) $event->id)
            ->where('event.title', 'Editable Event')
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
