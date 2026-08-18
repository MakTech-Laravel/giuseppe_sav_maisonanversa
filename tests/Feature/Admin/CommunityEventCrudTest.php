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

test('events index exposes starts_at capacity and rsvp_count', function () {
    CommunityEvent::factory()->create([
        'title' => 'Heritage Meetup',
        'capacity' => 20,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.events.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/index')
            ->has('events', 1)
            ->where('events.0.title', 'Heritage Meetup')
            ->where('events.0.capacity', 20)
            ->where('events.0.rsvp_count', 0)
            ->has('events.0.starts_at')
            ->has('events.0.location')
        );
});
