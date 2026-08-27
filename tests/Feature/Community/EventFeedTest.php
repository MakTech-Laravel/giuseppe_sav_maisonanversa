<?php

use App\Models\CommunityEvent;
use App\Models\EventRsvp;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('ended events drop out of the open tab', function () {
    $viewer = User::factory()->create();

    CommunityEvent::factory()->opening()->create();
    CommunityEvent::factory()->closed()->create();

    $this->actingAs($viewer)
        ->get(route('community.events.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/events/index')
            ->where('tab', 'open')
            ->where('counts.open', 1)
        );
});

test('the past tab only lists events the member booked', function () {
    $viewer = User::factory()->create();

    $joined = CommunityEvent::factory()->closed()->create();
    EventRsvp::factory()->create([
        'community_event_id' => $joined->id,
        'user_id' => $viewer->id,
    ]);

    CommunityEvent::factory()->closed()->create();

    $this->actingAs($viewer)
        ->get(route('community.events.index', ['locale' => 'nl', 'tab' => 'past']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('tab', 'past')
            ->where('counts.past', 1)
        );
});

test('the mine tab lists upcoming events the member booked', function () {
    $viewer = User::factory()->create();

    $upcoming = CommunityEvent::factory()->opening()->create();
    EventRsvp::factory()->create([
        'community_event_id' => $upcoming->id,
        'user_id' => $viewer->id,
    ]);

    CommunityEvent::factory()->opening()->create();

    $this->actingAs($viewer)
        ->get(route('community.events.index', ['locale' => 'nl', 'tab' => 'mine']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('tab', 'mine')
            ->where('counts.mine', 1)
        );
});

test('guests are redirected away from the events page', function () {
    $this->get(route('community.events.index', ['locale' => 'nl']))
        ->assertRedirect();
});
