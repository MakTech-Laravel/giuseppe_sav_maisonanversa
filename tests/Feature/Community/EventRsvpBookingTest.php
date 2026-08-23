<?php

use App\Enums\RoleEnum;
use App\Mail\RsvpConfirmation;
use App\Models\CommunityEvent;
use App\Models\EventRsvp;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('guests cannot rsvp to community events', function () {
    $event = CommunityEvent::factory()->create();

    $this->post(route('community.events.rsvp', [
        'locale' => 'nl',
        'communityEvent' => $event->id,
    ]))->assertRedirect();

    expect(EventRsvp::query()->where('community_event_id', $event->id)->exists())->toBeFalse();
});

test('authenticated members can book an event', function () {
    Mail::fake();

    $user = User::factory()->create();
    $event = CommunityEvent::factory()->create(['capacity' => 10]);

    $this->actingAs($user)
        ->post(route('community.events.rsvp', [
            'locale' => 'nl',
            'communityEvent' => $event->id,
        ]))
        ->assertRedirect();

    expect(EventRsvp::query()->where([
        'community_event_id' => $event->id,
        'user_id' => $user->id,
    ])->exists())->toBeTrue();

    Mail::assertQueued(RsvpConfirmation::class);
});

test('authenticated members can cancel their booking', function () {
    $user = User::factory()->create();
    $event = CommunityEvent::factory()->create();

    EventRsvp::factory()->create([
        'community_event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->delete(route('community.events.rsvp.cancel', [
            'locale' => 'nl',
            'communityEvent' => $event->id,
        ]))
        ->assertRedirect();

    expect(EventRsvp::query()->where([
        'community_event_id' => $event->id,
        'user_id' => $user->id,
    ])->exists())->toBeFalse();
});

test('booking is rejected when the event is full', function () {
    $event = CommunityEvent::factory()->create(['capacity' => 1]);
    $occupant = User::factory()->create();
    $user = User::factory()->create();

    EventRsvp::factory()->create([
        'community_event_id' => $event->id,
        'user_id' => $occupant->id,
    ]);

    $this->actingAs($user)
        ->post(route('community.events.rsvp', [
            'locale' => 'nl',
            'communityEvent' => $event->id,
        ]))
        ->assertStatus(422);

    expect(EventRsvp::query()->where([
        'community_event_id' => $event->id,
        'user_id' => $user->id,
    ])->exists())->toBeFalse();
});

test('admin event show lists bookings with minimum details', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    $member = User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    $event = CommunityEvent::factory()->create(['title' => 'Circle Night']);

    EventRsvp::factory()->create([
        'community_event_id' => $event->id,
        'user_id' => $member->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.events.show', ['locale' => 'nl', 'event' => $event->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/show')
            ->where('event.title', 'Circle Night')
            ->has('event.bookings', 1)
            ->where('event.bookings.0.user_id', (string) $member->id)
            ->where('event.bookings.0.name', 'Ada Lovelace')
            ->where('event.bookings.0.email', 'ada@example.com')
            ->has('event.bookings.0.booked_at')
            ->has('event.thumbnail_url')
        );
});

test('staff can upload an event thumbnail', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    $this->actingAs($admin)
        ->post(route('admin.events.store', ['locale' => 'nl']), [
            'title' => 'Thumbnail Night',
            'description' => 'With cover image',
            'starts_at' => now()->addWeek()->toDateTimeString(),
            'location' => 'Antwerp',
            'capacity' => 20,
            'thumbnail' => UploadedFile::fake()->image('event.jpg'),
        ])
        ->assertRedirect();

    $event = CommunityEvent::query()->where('title', 'Thumbnail Night')->first();

    expect($event)->not->toBeNull()
        ->and($event->thumbnail)->not->toBeNull();

    Storage::disk('public')->assertExists($event->thumbnail);
});
