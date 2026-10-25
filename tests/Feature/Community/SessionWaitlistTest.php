<?php

use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionParticipantStatus;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Mail::fake();
    Queue::fake();

    $this->host = User::factory()->create();
    $this->session = CommunitySession::factory()->requiresRequests()->create([
        'host_id' => $this->host->id,
        'capacity' => 3,
    ]);

    waitlistSeat($this->session, $this->host);
});

function waitlistSeat(CommunitySession $session, User $user, SessionParticipantStatus $status = SessionParticipantStatus::Confirmed): void
{
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $user->id,
        'status' => $status,
    ]);
}

function waitlistRoute(string $name, CommunitySession $session, ?User $user = null): string
{
    return route($name, array_filter([
        'locale' => 'nl',
        'communitySession' => $session->id,
        'user' => $user?->id,
    ]));
}

function waitlistStatus(CommunitySession $session, User $user): ?SessionParticipantStatus
{
    return CommunitySessionParticipant::query()
        ->where('community_session_id', $session->id)
        ->where('user_id', $user->id)
        ->first()
        ?->status;
}

test('joining puts the member on the waiting list instead of confirming them', function () {
    $member = User::factory()->create();

    $this->actingAs($member)
        ->post(waitlistRoute('community.sessions.join', $this->session))
        ->assertRedirect();

    expect(waitlistStatus($this->session, $member))->toBe(SessionParticipantStatus::Pending)
        ->and($this->session->participantsCount())->toBe(1)
        ->and($this->session->pendingCount())->toBe(1)
        ->and($this->session->players()->pluck('users.id')->all())->toBe([$this->host->id]);
});

test('pending requests are capped at the seats left beside the confirmed players', function () {
    [$first, $second, $third] = User::factory()->count(3)->create();

    $this->actingAs($first)->post(waitlistRoute('community.sessions.join', $this->session))->assertRedirect();
    $this->actingAs($second)->post(waitlistRoute('community.sessions.join', $this->session))->assertRedirect();

    $this->actingAs($third)
        ->post(waitlistRoute('community.sessions.join', $this->session))
        ->assertStatus(422);

    expect($this->session->pendingCount())->toBe(2)
        ->and(waitlistStatus($this->session, $third))->toBeNull();
});

test('withdrawing a request frees the slot for another member', function () {
    [$first, $second, $third] = User::factory()->count(3)->create();
    waitlistSeat($this->session, $first, SessionParticipantStatus::Pending);
    waitlistSeat($this->session, $second, SessionParticipantStatus::Pending);

    $this->actingAs($first)
        ->delete(waitlistRoute('community.sessions.leave', $this->session))
        ->assertRedirect();

    expect(waitlistStatus($this->session, $first))->toBeNull();

    $this->actingAs($third)
        ->post(waitlistRoute('community.sessions.join', $this->session))
        ->assertRedirect();

    expect(waitlistStatus($this->session, $third))->toBe(SessionParticipantStatus::Pending);
});

test('a confirmed player leaving frees a seat for a new request', function () {
    [$player, $waiting, $newcomer] = User::factory()->count(3)->create();
    waitlistSeat($this->session, $player);
    waitlistSeat($this->session, $waiting, SessionParticipantStatus::Pending);

    $this->actingAs($newcomer)->post(waitlistRoute('community.sessions.join', $this->session))->assertStatus(422);

    $this->actingAs($player)->delete(waitlistRoute('community.sessions.leave', $this->session))->assertRedirect();

    $this->actingAs($newcomer)->post(waitlistRoute('community.sessions.join', $this->session))->assertRedirect();

    expect(waitlistStatus($this->session, $newcomer))->toBe(SessionParticipantStatus::Pending);
});

test('the host approving a request confirms the player and updates the seats', function () {
    $member = User::factory()->create();
    waitlistSeat($this->session, $member, SessionParticipantStatus::Pending);

    $this->actingAs($this->host)
        ->patch(waitlistRoute('community.sessions.requests.approve', $this->session, $member))
        ->assertRedirect();

    $session = $this->session->fresh();

    expect(waitlistStatus($session, $member))->toBe(SessionParticipantStatus::Confirmed)
        ->and($session->participantsCount())->toBe(2)
        ->and($session->pendingCount())->toBe(0)
        ->and($session->openSlots())->toBe(1);
});

test('a rejected member cannot ask again for the same session', function () {
    $member = User::factory()->create();
    waitlistSeat($this->session, $member, SessionParticipantStatus::Pending);

    $this->actingAs($this->host)
        ->patch(waitlistRoute('community.sessions.requests.reject', $this->session, $member))
        ->assertRedirect();

    expect(waitlistStatus($this->session, $member))->toBe(SessionParticipantStatus::Rejected)
        ->and($this->session->pendingCount())->toBe(0);

    $this->actingAs($member)
        ->post(waitlistRoute('community.sessions.join', $this->session))
        ->assertStatus(422);

    $this->actingAs($member)
        ->delete(waitlistRoute('community.sessions.leave', $this->session))
        ->assertRedirect();

    expect(waitlistStatus($this->session, $member))->toBe(SessionParticipantStatus::Rejected);
});

test('a repeated join request does not create a second row', function () {
    $member = User::factory()->create();

    $this->actingAs($member)->post(waitlistRoute('community.sessions.join', $this->session))->assertRedirect();
    $this->actingAs($member)->post(waitlistRoute('community.sessions.join', $this->session))->assertRedirect();

    expect(CommunitySessionParticipant::query()
        ->where('community_session_id', $this->session->id)
        ->where('user_id', $member->id)
        ->count())->toBe(1);
});

test('only the host can approve or reject requests', function () {
    $member = User::factory()->create();
    $stranger = User::factory()->create();
    $admin = User::factory()->admin()->create();
    waitlistSeat($this->session, $member, SessionParticipantStatus::Pending);

    foreach ([$member, $stranger, $admin] as $actor) {
        $this->actingAs($actor)
            ->patch(waitlistRoute('community.sessions.requests.approve', $this->session, $member))
            ->assertForbidden();

        $this->actingAs($actor)
            ->patch(waitlistRoute('community.sessions.requests.reject', $this->session, $member))
            ->assertForbidden();
    }

    expect(waitlistStatus($this->session, $member))->toBe(SessionParticipantStatus::Pending);
});

test('approving cannot overbook the confirmed seats', function () {
    [$first, $second, $late] = User::factory()->count(3)->create();
    waitlistSeat($this->session, $first);
    waitlistSeat($this->session, $second);
    waitlistSeat($this->session, $late, SessionParticipantStatus::Pending);

    $this->actingAs($this->host)
        ->patch(waitlistRoute('community.sessions.requests.approve', $this->session, $late))
        ->assertStatus(422);

    expect($this->session->participantsCount())->toBe(3)
        ->and(waitlistStatus($this->session, $late))->toBe(SessionParticipantStatus::Pending);
});

test('the unique index rejects a duplicate participant row', function () {
    $member = User::factory()->create();
    waitlistSeat($this->session, $member, SessionParticipantStatus::Pending);

    expect(fn () => waitlistSeat($this->session, $member))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('past and cancelled sessions refuse requests', function () {
    $past = CommunitySession::factory()->past()->create();
    $cancelled = CommunitySession::factory()->cancelled()->create();
    $member = User::factory()->create();

    $this->actingAs($member)->post(waitlistRoute('community.sessions.join', $past))->assertStatus(422);
    $this->actingAs($member)->post(waitlistRoute('community.sessions.join', $cancelled))->assertStatus(422);
});

test('the session page shows the host their pending requests and the member their own state', function () {
    $member = User::factory()->create();
    waitlistSeat($this->session, $member, SessionParticipantStatus::Pending);

    $this->actingAs($this->host)
        ->get(waitlistRoute('community.sessions.show', $this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/sessions/show')
            ->has('joinRequests', 1)
            ->where('joinRequests.0.id', $member->id)
            ->where('session.pending_count', 1)
            ->where('session.open_slots', 1)
            ->where('scoreboard', null)
        );

    $this->actingAs($member)
        ->get(waitlistRoute('community.sessions.show', $this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('joinRequests', 0)
            ->where('session.viewer_status', 'pending')
            ->where('session.is_pending', true)
            ->where('session.can_withdraw', true)
            ->where('session.can_join', false)
            ->where('session.is_joined', false)
        );
});

test('a pending request shows in the member\'s mine tab', function () {
    $member = User::factory()->create();
    waitlistSeat($this->session, $member, SessionParticipantStatus::Pending);

    $this->actingAs($member)
        ->get(route('community.sessions.index', ['locale' => 'nl', 'tab' => 'mine']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('counts.mine', 1));
});

test('session cards expose the waiting list to the pending member and the host', function () {
    $member = User::factory()->create();
    waitlistSeat($this->session, $member, SessionParticipantStatus::Pending);

    $this->actingAs($member)
        ->get(route('community.sessions.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sessions.data.0.id', (string) $this->session->id)
            ->where('sessions.data.0.pending_count', 1)
            ->where('sessions.data.0.open_slots', 1)
            ->where('sessions.data.0.is_pending', true)
            ->where('sessions.data.0.can_withdraw', true)
            ->where('sessions.data.0.can_join', false)
        );

    $this->actingAs($this->host)
        ->get(route('community.sessions.index', ['locale' => 'nl', 'tab' => 'mine']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sessions.data.0.id', (string) $this->session->id)
            ->where('sessions.data.0.is_host', true)
            ->where('sessions.data.0.pending_count', 1)
        );
});

test('seat counts stay within capacity once all free seats are requested', function () {
    [$first, $second, $outsider] = User::factory()->count(3)->create();
    waitlistSeat($this->session, $first, SessionParticipantStatus::Pending);
    waitlistSeat($this->session, $second, SessionParticipantStatus::Pending);

    $this->actingAs($outsider)
        ->get(waitlistRoute('community.sessions.show', $this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.participants_count', 1)
            ->where('session.pending_count', 2)
            ->where('session.open_slots', 0)
            ->where('session.is_full', false)
            ->where('session.can_join', false)
        );
});

test('stale pending requests on a past session no longer hold seats on the card', function () {
    $past = CommunitySession::factory()->past()->create([
        'host_id' => $this->host->id,
        'capacity' => 4,
    ]);
    waitlistSeat($past, $this->host);
    waitlistSeat($past, User::factory()->create(), SessionParticipantStatus::Pending);

    $this->actingAs($this->host)
        ->get(waitlistRoute('community.sessions.show', $past))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.pending_count', 0)
            ->where('session.open_slots', 3)
        );
});

test('the approve and reject forms submit with a spoofed patch method', function () {
    [$approved, $declined] = User::factory()->count(2)->create();
    waitlistSeat($this->session, $approved, SessionParticipantStatus::Pending);
    waitlistSeat($this->session, $declined, SessionParticipantStatus::Pending);

    $this->actingAs($this->host)
        ->post(waitlistRoute('community.sessions.requests.approve', $this->session, $approved).'?_method=PATCH')
        ->assertRedirect();

    $this->actingAs($this->host)
        ->post(waitlistRoute('community.sessions.requests.reject', $this->session, $declined).'?_method=PATCH')
        ->assertRedirect();

    expect(waitlistStatus($this->session, $approved))->toBe(SessionParticipantStatus::Confirmed)
        ->and(waitlistStatus($this->session, $declined))->toBe(SessionParticipantStatus::Rejected);
});

test('registration closes two hours before the start', function () {
    $this->session->update(['starts_at' => now()->addMinutes(119)]);
    $member = User::factory()->create();

    $this->actingAs($member)
        ->post(waitlistRoute('community.sessions.join', $this->session))
        ->assertStatus(422);

    expect(waitlistStatus($this->session, $member))->toBeNull();

    $this->actingAs($member)
        ->get(waitlistRoute('community.sessions.show', $this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.registration_closed', true)
            ->where('session.can_join', false)
        );
});

test('a member can still ask to join just before the registration deadline', function () {
    $this->session->update(['starts_at' => now()->addMinutes(121)]);
    $member = User::factory()->create();

    $this->actingAs($member)
        ->post(waitlistRoute('community.sessions.join', $this->session))
        ->assertRedirect();

    expect(waitlistStatus($this->session, $member))->toBe(SessionParticipantStatus::Pending);
});

test('a player can leave after registration closed until the start, which tells the organiser', function () {
    $this->session->update(['starts_at' => now()->addMinutes(30)]);
    $player = User::factory()->create();
    waitlistSeat($this->session, $player);

    $this->actingAs($player)
        ->get(waitlistRoute('community.sessions.show', $this->session))
        ->assertInertia(fn (Assert $page) => $page->where('session.can_leave', true));

    $this->actingAs($player)
        ->delete(waitlistRoute('community.sessions.leave', $this->session))
        ->assertRedirect();

    expect(waitlistStatus($this->session, $player))->toBeNull()
        ->and($this->host->userNotifications()->with('notification')->get()->pluck('notification.data.notice')->all())
        ->toBe(['spot_opened']);
});

test('a player can no longer leave once the session has started', function () {
    $this->session->update(['starts_at' => now()->subMinutes(10), 'duration_minutes' => 90]);
    $player = User::factory()->create();
    waitlistSeat($this->session, $player);

    $this->actingAs($player)
        ->delete(waitlistRoute('community.sessions.leave', $this->session))
        ->assertForbidden();

    expect(waitlistStatus($this->session, $player))->toBe(SessionParticipantStatus::Confirmed);
});

test('the host can still decide earlier requests after the deadline, until the start', function () {
    $member = User::factory()->create();
    waitlistSeat($this->session, $member, SessionParticipantStatus::Pending);
    $this->session->update(['starts_at' => now()->addHour()]);

    $this->actingAs($this->host)
        ->patch(waitlistRoute('community.sessions.requests.approve', $this->session, $member))
        ->assertRedirect();

    expect(waitlistStatus($this->session, $member))->toBe(SessionParticipantStatus::Confirmed);

    $late = User::factory()->create();
    waitlistSeat($this->session, $late, SessionParticipantStatus::Pending);
    $this->session->update(['starts_at' => now()->subMinutes(5), 'duration_minutes' => 90]);

    $this->actingAs($this->host)
        ->patch(waitlistRoute('community.sessions.requests.approve', $this->session, $late))
        ->assertForbidden();
});

test('open sessions confirm joins immediately without a host decision', function () {
    $open = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'capacity' => 3,
        'requests_required' => false,
    ]);
    waitlistSeat($open, $this->host);

    $member = User::factory()->create();

    $this->actingAs($member)
        ->post(waitlistRoute('community.sessions.join', $open))
        ->assertRedirect();

    expect(waitlistStatus($open, $member))->toBe(SessionParticipantStatus::Confirmed)
        ->and($open->participantsCount())->toBe(2)
        ->and($open->pendingCount())->toBe(0);
});

test('creating a session defaults to open joins unless requests_required is set', function () {
    $club = Club::factory()->create();

    $this->actingAs($this->host)
        ->post(route('community.sessions.store', ['locale' => 'nl']), [
            'sport' => SessionSport::Padel->value,
            'club_id' => $club->id,
            'starts_at' => now()->addDay()->toDateTimeString(),
            'duration_minutes' => 90,
            'court_status' => SessionCourtStatus::NotBooked->value,
            'level' => SessionLevel::Intermediate->value,
            'gender' => SessionGender::Everyone->value,
            'capacity' => 4,
        ])
        ->assertRedirect();

    $session = CommunitySession::query()->latest('id')->first();

    expect($session)->not->toBeNull()
        ->and($session->requests_required)->toBeFalse();
});
