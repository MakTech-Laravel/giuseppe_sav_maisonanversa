<?php

use App\Enums\SessionParticipantStatus;
use App\Models\CommunitySession;
use App\Models\CommunitySessionMessage;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Services\Community\SessionChatService;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Broadcast;

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'starts_at' => now()->addDays(2),
    ]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $this->host->id,
        'status' => SessionParticipantStatus::Confirmed,
        'for_chat_only' => false,
    ]);
});

function failBroadcastQueue(): void
{
    $real = app(BroadcastManager::class);
    $mock = Mockery::mock($real)->makePartial();
    $mock->shouldReceive('queue')
        ->andThrow(new BroadcastException('Failed to connect to 127.0.0.1 port 8080'));

    app()->instance(BroadcastManager::class, $mock);
    Broadcast::swap($mock);
}

test('member send persists and redirects when Reverb broadcast fails', function () {
    failBroadcastQueue();

    $player = User::factory()->create();
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $player->id,
        'status' => SessionParticipantStatus::Confirmed,
        'for_chat_only' => false,
    ]);

    $this->actingAs($player)
        ->post(route('member.chat.messages.store', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
        ]), ['body' => 'Still saved'])
        ->assertRedirect();

    $message = CommunitySessionMessage::query()->where('body', 'Still saved')->first();

    expect($message)->not->toBeNull()
        ->and($message->is_system)->toBeFalse()
        ->and($message->user_id)->toBe($player->id);
});

test('service send returns the message when broadcast throws', function () {
    failBroadcastQueue();

    $message = app(SessionChatService::class)->send(
        $this->session,
        $this->host,
        'Via service',
        [],
    );

    expect($message->exists)->toBeTrue()
        ->and(CommunitySessionMessage::query()->whereKey($message->id)->exists())->toBeTrue()
        ->and($message->body)->toBe('Via service');
});
