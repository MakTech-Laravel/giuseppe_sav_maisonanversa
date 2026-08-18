<?php

use App\Enums\RoleEnum;
use App\Mail\RsvpConfirmation;
use App\Models\CommunityEvent;
use App\Models\EventRsvp;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('rsvp queues a confirmation email on first create', function () {
    Mail::fake();

    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    $event = CommunityEvent::factory()->create();

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

    Mail::assertQueued(RsvpConfirmation::class, function (RsvpConfirmation $mail) use ($event) {
        return $mail->event->is($event);
    });
});

test('duplicate rsvp does not queue another confirmation email', function () {
    Mail::fake();

    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    $event = CommunityEvent::factory()->create();

    EventRsvp::factory()->create([
        'community_event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('community.events.rsvp', [
            'locale' => 'nl',
            'communityEvent' => $event->id,
        ]))
        ->assertRedirect();

    Mail::assertNothingQueued();
});
