<?php

use App\Models\CommunitySession;
use App\Models\CommunitySessionMessage;
use App\Models\CommunitySessionMessageAttachment;
use App\Models\User;
use App\Services\Community\SessionChatService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

test('the prune command deletes messages only after thirty days past close', function () {
    Storage::fake('public');

    $host = User::factory()->create();

    $oldClosed = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->subDays(40),
        'cancelled_at' => null,
    ]);
    $oldClosed->refresh();

    $recentClosed = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->subDays(2),
        'cancelled_at' => null,
    ]);
    $recentClosed->refresh();

    $cancelledOld = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->addDays(3),
        'cancelled_at' => now()->subDays(35),
    ]);

    $stillOpen = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->addDays(4),
    ]);

    $oldMessage = CommunitySessionMessage::factory()->create([
        'community_session_id' => $oldClosed->id,
        'user_id' => $host->id,
        'body' => 'oud',
    ]);
    $oldPath = "session-chat/{$oldClosed->id}/old.jpg";
    Storage::disk('public')->put($oldPath, 'fake');
    CommunitySessionMessageAttachment::factory()->create([
        'message_id' => $oldMessage->id,
        'path' => $oldPath,
    ]);

    $recentMessage = CommunitySessionMessage::factory()->create([
        'community_session_id' => $recentClosed->id,
        'user_id' => $host->id,
        'body' => 'recent',
    ]);

    $cancelledMessage = CommunitySessionMessage::factory()->create([
        'community_session_id' => $cancelledOld->id,
        'user_id' => $host->id,
        'body' => 'geannuleerd',
    ]);

    $openMessage = CommunitySessionMessage::factory()->create([
        'community_session_id' => $stillOpen->id,
        'user_id' => $host->id,
        'body' => 'open',
    ]);

    expect(app(SessionChatService::class)->closedAt($oldClosed)?->lte(now()->subDays(30)))->toBeTrue()
        ->and(app(SessionChatService::class)->closedAt($recentClosed)?->lte(now()->subDays(30)))->toBeFalse();

    Artisan::call('sessions:prune-chat-messages');

    expect(CommunitySessionMessage::query()->find($oldMessage->id))->toBeNull()
        ->and(CommunitySessionMessage::query()->find($cancelledMessage->id))->toBeNull()
        ->and(CommunitySessionMessage::query()->find($recentMessage->id))->not->toBeNull()
        ->and(CommunitySessionMessage::query()->find($openMessage->id))->not->toBeNull();

    Storage::disk('public')->assertMissing($oldPath);
});

test('session chat stays writable for forty-eight hours after the end', function () {
    $service = app(SessionChatService::class);
    $host = User::factory()->create();

    $open = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->addDay(),
    ]);

    expect($service->isOpen($open))->toBeTrue()
        ->and($service->closedAt($open))->toBeNull()
        ->and($service->messagesPurgeAt($open))->toBeNull();

    $justEnded = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->subHours(3),
        'duration_minutes' => 90,
    ]);
    $justEnded->refresh();

    expect($service->isOpen($justEnded))->toBeTrue()
        ->and($service->closedAt($justEnded)?->equalTo($justEnded->ends_at))->toBeTrue();

    $longPast = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->subDays(3),
        'duration_minutes' => 90,
    ]);
    $longPast->refresh();

    expect($service->isOpen($longPast))->toBeFalse()
        ->and($service->messagesPurgeAt($longPast)?->equalTo($longPast->ends_at->copy()->addDays(30)))->toBeTrue();

    $cancelled = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->addDays(2),
        'cancelled_at' => now()->subHour(),
    ]);

    expect($service->isOpen($cancelled))->toBeFalse()
        ->and($service->closedAt($cancelled)?->equalTo($cancelled->cancelled_at))->toBeTrue();
});

test('retention helpers match the thirty-day purge window', function () {
    $service = app(SessionChatService::class);
    $host = User::factory()->create();

    $open = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->addDay(),
    ]);

    $recent = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->subDays(2),
        'cancelled_at' => null,
    ]);
    $recent->refresh();

    $old = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'starts_at' => now()->subDays(40),
        'cancelled_at' => null,
    ]);
    $old->refresh();

    expect($service->isWithinRetention($open))->toBeTrue()
        ->and($service->isWithinRetention($recent))->toBeTrue()
        ->and($service->isWithinRetention($old))->toBeFalse();
});
