<?php

use App\Enums\SessionParticipantStatus;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->session = CommunitySession::factory()->create(['host_id' => $this->host->id]);

    chatPageSeat($this->session, $this->host);
});

function chatPageSeat(CommunitySession $session, User $user, SessionParticipantStatus $status = SessionParticipantStatus::Confirmed): void
{
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $user->id,
        'status' => $status,
    ]);
}

function chatPageUrl(CommunitySession $session): string
{
    return route('community.sessions.chat', ['locale' => 'nl', 'communitySession' => $session->id]);
}

test('the host opens the session chat', function () {
    $this->actingAs($this->host)
        ->get(chatPageUrl($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/sessions/chat')
            ->where('session.id', (string) $this->session->id)
            ->where('session.is_host', true)
            ->where('viewer.id', $this->host->id)
            ->where('messages', []));
});

test('an approved player opens the session chat', function () {
    $player = User::factory()->create();
    chatPageSeat($this->session, $player);

    $this->actingAs($player)
        ->get(chatPageUrl($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/sessions/chat')
            ->where('session.is_joined', true)
            ->where('viewer.id', $player->id));
});

test('the chat stays readable after the session is cancelled', function () {
    $this->session->update(['cancelled_at' => now()]);

    $this->actingAs($this->host)
        ->get(chatPageUrl($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/sessions/chat')
            ->where('session.is_cancelled', true));
});

test('pending and rejected requests cannot open the chat', function (SessionParticipantStatus $status) {
    $member = User::factory()->create();
    chatPageSeat($this->session, $member, $status);

    $this->actingAs($member)
        ->get(chatPageUrl($this->session))
        ->assertForbidden();
})->with([
    'pending' => SessionParticipantStatus::Pending,
    'rejected' => SessionParticipantStatus::Rejected,
]);

test('members outside the session cannot open the chat', function () {
    $this->actingAs(User::factory()->create())
        ->get(chatPageUrl($this->session))
        ->assertForbidden();
});

test('guests are sent to log in', function () {
    $this->get(chatPageUrl($this->session))->assertRedirect();
});
