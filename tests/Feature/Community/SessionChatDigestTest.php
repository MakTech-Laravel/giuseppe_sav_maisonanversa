<?php

use App\Console\Commands\Community\SendSessionChatDigestsCommand;
use App\Enums\NotificationType;
use App\Enums\SessionParticipantStatus;
use App\Events\SessionChatActivity;
use App\Events\SessionMessageCreated;
use App\Mail\SessionChatDigestMail;
use App\Models\AppNotification;
use App\Models\CommunitySession;
use App\Models\CommunitySessionMessage;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Community\SessionChatService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->host = User::factory()->create([
        'last_seen_at' => now()->subHour(),
        'chat_digest_sent_at' => null,
        'session_emails' => true,
    ]);
    $this->session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'starts_at' => now()->addDays(2),
    ]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $this->host->id,
        'status' => SessionParticipantStatus::Confirmed,
    ]);
});

function unreadMessageFor(CommunitySession $session, User $from, string $body = 'Hallo'): CommunitySessionMessage
{
    return CommunitySessionMessage::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $from->id,
        'body' => $body,
    ]);
}

test('the digest command notifies an inactive member with unread chat once', function () {
    Mail::fake();

    $player = User::factory()->create();
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $player->id,
        'status' => SessionParticipantStatus::Confirmed,
    ]);

    unreadMessageFor($this->session, $player);

    $this->artisan(SendSessionChatDigestsCommand::class)
        ->assertSuccessful();

    $row = $this->host->userNotifications()->with('notification')->first();

    expect($row)->not->toBeNull()
        ->and($row->notification->type)->toBe(NotificationType::Community)
        ->and($row->notification->title)->toBe('Nieuwe berichten')
        ->and($row->notification->message)->toContain('1')
        ->and($row->notification->data['digest'])->toBe('session_chat')
        ->and($this->host->fresh()->chat_digest_sent_at)->not->toBeNull();

    Mail::assertQueued(SessionChatDigestMail::class, function (SessionChatDigestMail $mail): bool {
        return $mail->hasTo($this->host->email)
            && $mail->unreadCount === 1;
    });
});

test('the digest command skips active members', function () {
    Mail::fake();

    $player = User::factory()->create();
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $player->id,
        'status' => SessionParticipantStatus::Confirmed,
    ]);

    unreadMessageFor($this->session, $player);

    $this->host->forceFill(['last_seen_at' => now()])->save();

    $this->artisan(SendSessionChatDigestsCommand::class)
        ->assertSuccessful();

    expect(UserNotification::query()->where('user_id', $this->host->id)->count())->toBe(0);
    Mail::assertNothingQueued();
});

test('the digest command skips members with zero unread', function () {
    Mail::fake();

    $this->artisan(SendSessionChatDigestsCommand::class)
        ->assertSuccessful();

    expect(UserNotification::query()->where('user_id', $this->host->id)->count())->toBe(0);
    Mail::assertNothingQueued();
});

test('the digest watermark prevents duplicate digests for the same unread pile', function () {
    Mail::fake();

    $player = User::factory()->create();
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $player->id,
        'status' => SessionParticipantStatus::Confirmed,
    ]);

    unreadMessageFor($this->session, $player);

    $this->artisan(SendSessionChatDigestsCommand::class)->assertSuccessful();
    expect(UserNotification::query()->where('user_id', $this->host->id)->count())->toBe(1);

    Mail::fake();

    $this->artisan(SendSessionChatDigestsCommand::class)->assertSuccessful();

    expect(UserNotification::query()->where('user_id', $this->host->id)->count())->toBe(1);
    Mail::assertNothingQueued();
});

test('a newer unread message after the watermark allows another digest', function () {
    Mail::fake();

    $player = User::factory()->create();
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $player->id,
        'status' => SessionParticipantStatus::Confirmed,
    ]);

    unreadMessageFor($this->session, $player, 'Eerste');

    $this->artisan(SendSessionChatDigestsCommand::class)->assertSuccessful();

    $this->host->forceFill([
        'chat_digest_sent_at' => now()->subMinute(),
        'last_seen_at' => now()->subHour(),
    ])->save();

    $this->travel(2)->seconds();

    unreadMessageFor($this->session, $player, 'Tweede');

    $this->artisan(SendSessionChatDigestsCommand::class)->assertSuccessful();

    expect(UserNotification::query()->where('user_id', $this->host->id)->count())->toBe(2);
});

test('digest email respects session_emails preference while in-app still sends', function () {
    Mail::fake();

    $this->host->forceFill(['session_emails' => false])->save();

    $player = User::factory()->create();
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $player->id,
        'status' => SessionParticipantStatus::Confirmed,
    ]);

    unreadMessageFor($this->session, $player);

    $this->artisan(SendSessionChatDigestsCommand::class)->assertSuccessful();

    expect(UserNotification::query()->where('user_id', $this->host->id)->count())->toBe(1);
    Mail::assertNothingQueued();
});

test('sending a chat message does not create an in-app notification', function () {
    Event::fake([SessionMessageCreated::class, SessionChatActivity::class]);

    $player = User::factory()->create();
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $player->id,
        'status' => SessionParticipantStatus::Confirmed,
    ]);

    app(SessionChatService::class)->send($this->session, $player, 'Geen inbox', []);

    expect(AppNotification::query()->count())->toBe(0)
        ->and(UserNotification::query()->count())->toBe(0);
});

test('presence endpoint updates last_seen_at', function () {
    $this->host->forceFill(['last_seen_at' => null])->save();

    $this->actingAs($this->host)
        ->post(route('member.presence', ['locale' => 'nl']))
        ->assertNoContent();

    expect($this->host->fresh()->last_seen_at)->not->toBeNull()
        ->and($this->host->fresh()->last_seen_at->greaterThan(now()->subMinute()))->toBeTrue();
});
