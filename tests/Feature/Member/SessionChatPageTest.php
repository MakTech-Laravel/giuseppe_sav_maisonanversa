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

function memberChatShowUrl(CommunitySession $session): string
{
    return route('member.chat.show', ['locale' => 'nl', 'communitySession' => $session->id]);
}

function memberChatIndexUrl(): string
{
    return route('member.chat.index', ['locale' => 'nl']);
}

test('the host opens the session chat inbox', function () {
    $this->actingAs($this->host)
        ->get(memberChatShowUrl($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/chat')
            ->where('activeSession.id', (string) $this->session->id)
            ->where('activeSession.is_host', true)
            ->where('viewer.id', $this->host->id)
            ->where('messages', [])
            ->where('can_send', true)
            ->where('messages_purge_at', null)
            ->has('unread_counts')
            ->has('sessions', 1));
});

test('an approved player opens the session chat inbox', function () {
    $player = User::factory()->create();
    chatPageSeat($this->session, $player);

    $this->actingAs($player)
        ->get(memberChatShowUrl($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/chat')
            ->where('activeSession.is_joined', true)
            ->where('viewer.id', $player->id));
});

test('the chat inbox lists only chat-eligible sessions', function () {
    $player = User::factory()->create();
    chatPageSeat($this->session, $player);

    $pendingOnly = CommunitySession::factory()->create(['host_id' => $this->host->id]);
    chatPageSeat($pendingOnly, $this->host);
    chatPageSeat($pendingOnly, $player, SessionParticipantStatus::Pending);

    $outsiderSession = CommunitySession::factory()->create();
    chatPageSeat($outsiderSession, $outsiderSession->host);

    $this->actingAs($player)
        ->get(memberChatIndexUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/chat')
            ->where('activeSession', null)
            ->has('sessions', 1)
            ->where('sessions.0.id', (string) $this->session->id));
});

test('the chat stays readable after the session is cancelled', function () {
    $this->session->update(['cancelled_at' => now()]);

    $this->actingAs($this->host)
        ->get(memberChatShowUrl($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/chat')
            ->where('activeSession.is_cancelled', true)
            ->where('can_send', false)
            ->whereNotNull('messages_purge_at'));
});

test('sessions past the seven-day retention window leave the chat list', function () {
    $purged = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'starts_at' => now()->subDays(10),
        'cancelled_at' => null,
    ]);
    $purged->refresh();
    chatPageSeat($purged, $this->host);

    $recentClosed = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'starts_at' => now()->subDays(2),
        'cancelled_at' => null,
    ]);
    $recentClosed->refresh();
    chatPageSeat($recentClosed, $this->host);

    $this->actingAs($this->host)
        ->get(memberChatIndexUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/chat')
            ->has('sessions', 2)
            ->where('sessions.0.id', (string) $this->session->id)
            ->where('sessions.1.id', (string) $recentClosed->id));
});

test('opening a purged session chat redirects to the inbox', function () {
    $purged = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'starts_at' => now()->subDays(10),
        'cancelled_at' => null,
    ]);
    $purged->refresh();
    chatPageSeat($purged, $this->host);

    $this->actingAs($this->host)
        ->get(memberChatShowUrl($purged))
        ->assertRedirect(memberChatIndexUrl());
});

test('pending and rejected requests cannot open the chat', function (SessionParticipantStatus $status) {
    $member = User::factory()->create();
    chatPageSeat($this->session, $member, $status);

    $this->actingAs($member)
        ->get(memberChatShowUrl($this->session))
        ->assertForbidden();
})->with([
    'pending' => SessionParticipantStatus::Pending,
    'rejected' => SessionParticipantStatus::Rejected,
]);

test('members outside the session cannot open the chat', function () {
    $this->actingAs(User::factory()->create())
        ->get(memberChatShowUrl($this->session))
        ->assertForbidden();
});

test('guests are sent to log in', function () {
    $this->get(memberChatShowUrl($this->session))->assertRedirect();
});

test('the old community chat route redirects to the member inbox', function () {
    $this->actingAs($this->host)
        ->get(route('community.sessions.chat', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
        ]))
        ->assertRedirect(memberChatShowUrl($this->session));
});
