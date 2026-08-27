<?php

use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function sessionPayload(Club $club, array $overrides = []): array
{
    return [
        'sport' => SessionSport::Padel->value,
        'club_id' => $club->id,
        'starts_at' => now()->addDay()->toDateTimeString(),
        'duration_minutes' => 90,
        'court_status' => SessionCourtStatus::Booked->value,
        'level' => SessionLevel::OpenToAll->value,
        'gender' => SessionGender::Everyone->value,
        'capacity' => 4,
        ...$overrides,
    ];
}

test('creating a session seats the host in the first slot', function () {
    $host = User::factory()->create();
    $club = Club::factory()->create();

    $this->actingAs($host)
        ->post(route('community.sessions.store', ['locale' => 'nl']), sessionPayload($club))
        ->assertRedirect();

    $session = CommunitySession::query()->firstOrFail();

    expect($session->host_id)->toBe($host->id)
        ->and($session->club_id)->toBe($club->id)
        ->and($session->ends_at->toDateTimeString())
        ->toBe($session->starts_at->copy()->addMinutes(90)->toDateTimeString())
        ->and($session->participants()->where('user_id', $host->id)->exists())->toBeTrue()
        ->and($session->openSlots())->toBe(3);
});

test('a session can last twenty or forty minutes', function () {
    $host = User::factory()->create();
    $club = Club::factory()->create();

    $this->actingAs($host)
        ->post(route('community.sessions.store', ['locale' => 'nl']), sessionPayload($club, [
            'duration_minutes' => 20,
        ]))
        ->assertRedirect();

    $session = CommunitySession::query()->firstOrFail();

    expect($session->duration_minutes)->toBe(20)
        ->and($session->ends_at->toDateTimeString())
        ->toBe($session->starts_at->copy()->addMinutes(20)->toDateTimeString());
});

test('session duration must be one of the offered lengths', function () {
    $host = User::factory()->create();
    $club = Club::factory()->create();

    $this->actingAs($host)
        ->post(route('community.sessions.store', ['locale' => 'nl']), sessionPayload($club, [
            'duration_minutes' => 25,
        ]))
        ->assertSessionHasErrors('duration_minutes');
});

test('the create form offers twenty and forty minute durations', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('community.sessions.create', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('options.durations.0.value', 20)
            ->where('options.durations.1.value', 40)
        );
});

test('a session cannot be attached to a club that is still pending', function () {
    $host = User::factory()->create();
    $club = Club::factory()->pending()->create();

    $this->actingAs($host)
        ->post(route('community.sessions.store', ['locale' => 'nl']), sessionPayload($club))
        ->assertSessionHasErrors('club_id');

    expect(CommunitySession::query()->count())->toBe(0);
});

test('joining a full session is rejected', function () {
    $host = User::factory()->create();
    $session = CommunitySession::factory()->create(['host_id' => $host->id, 'capacity' => 2]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $host->id,
    ]);
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs(User::factory()->create())
        ->post(route('community.sessions.join', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertStatus(422);

    expect($session->participants()->count())->toBe(2);
});

test('joining a past session is rejected', function () {
    $session = CommunitySession::factory()->past()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('community.sessions.join', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertStatus(422);
});

test('joining a cancelled session is rejected', function () {
    $session = CommunitySession::factory()->cancelled()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('community.sessions.join', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertStatus(422);
});

test('the host can remove a player but not themselves', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $session = CommunitySession::factory()->create(['host_id' => $host->id]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $host->id,
    ]);
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $guest->id,
    ]);

    $this->actingAs($host)
        ->from(route('community.sessions.show', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->delete(route('community.sessions.participants.destroy', [
            'locale' => 'nl',
            'communitySession' => $session->id,
            'user' => $guest->id,
        ]))
        ->assertRedirect();

    expect($session->participants()->where('user_id', $guest->id)->exists())->toBeFalse();

    $this->actingAs($host)
        ->delete(route('community.sessions.participants.destroy', [
            'locale' => 'nl',
            'communitySession' => $session->id,
            'user' => $host->id,
        ]))
        ->assertStatus(422);
});

test('a non-host cannot remove players or cancel the session', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $session = CommunitySession::factory()->create(['host_id' => $host->id]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $host->id,
    ]);
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $guest->id,
    ]);

    $this->actingAs($guest)
        ->delete(route('community.sessions.participants.destroy', [
            'locale' => 'nl',
            'communitySession' => $session->id,
            'user' => $host->id,
        ]))
        ->assertForbidden();

    $this->actingAs($guest)
        ->delete(route('community.sessions.destroy', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertForbidden();
});

test('the host cancels instead of leaving their own session', function () {
    $host = User::factory()->create();
    $session = CommunitySession::factory()->create(['host_id' => $host->id]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $host->id,
    ]);

    $this->actingAs($host)
        ->delete(route('community.sessions.leave', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertForbidden();

    $this->actingAs($host)
        ->delete(route('community.sessions.destroy', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertRedirect();

    expect($session->fresh()->isCancelled())->toBeTrue();
});

test('past and cancelled sessions drop out of the open tab', function () {
    $viewer = User::factory()->create();

    CommunitySession::factory()->create();
    CommunitySession::factory()->past()->create();
    CommunitySession::factory()->cancelled()->create();

    $this->actingAs($viewer)
        ->get(route('community.sessions.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/sessions/index')
            ->where('tab', 'open')
            ->where('counts.open', 1)
        );
});

test('the past tab only lists sessions the member took part in', function () {
    $viewer = User::factory()->create();

    $joined = CommunitySession::factory()->past()->create();
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $joined->id,
        'user_id' => $viewer->id,
    ]);

    CommunitySession::factory()->past()->create();

    $this->actingAs($viewer)
        ->get(route('community.sessions.index', ['locale' => 'nl', 'tab' => 'past']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('tab', 'past')
            ->where('counts.past', 1)
        );
});

test('the open tab can be filtered by sport and city', function () {
    $viewer = User::factory()->create();

    $ghent = Club::factory()->create(['city' => 'Gent']);
    CommunitySession::factory()->tennis()->create(['club_id' => $ghent->id]);
    CommunitySession::factory()->create(['club_id' => Club::factory()->create(['city' => 'Brugge'])->id]);

    $this->actingAs($viewer)
        ->get(route('community.sessions.index', [
            'locale' => 'nl',
            'sport' => SessionSport::Tennis->value,
            'city' => 'Gent',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sport', SessionSport::Tennis->value)
            ->where('filters.city', 'Gent')
        );
});

test('guests are redirected away from the sessions page', function () {
    $this->get(route('community.sessions.index', ['locale' => 'nl']))
        ->assertRedirect();
});
