<?php

use App\Enums\RoleEnum;
use App\Enums\SessionParticipantStatus;
use App\Models\CommunitySession;
use App\Models\CommunitySessionMessage;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Services\Community\SessionChatService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->host = User::factory()->create();
    $this->session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'starts_at' => now()->addDays(2),
        'capacity' => 4,
    ]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $this->host->id,
        'status' => SessionParticipantStatus::Confirmed,
        'for_chat_only' => false,
    ]);
});

test('staff force-join send stores an is_system message', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    $this->actingAs($admin)
        ->post(route('admin.chat.messages.store', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
        ]), ['body' => 'Staff notice'])
        ->assertRedirect();

    $message = CommunitySessionMessage::query()->where('body', 'Staff notice')->first();

    expect($message)->not->toBeNull()
        ->and($message->is_system)->toBeTrue()
        ->and(app(SessionChatService::class)->present($message)['is_system'])->toBeTrue();
});

test('staff play-joined send stores a normal bubble message', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $admin->id,
        'status' => SessionParticipantStatus::Confirmed,
        'for_chat_only' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.chat.messages.store', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
        ]), ['body' => 'Ik speel mee'])
        ->assertRedirect();

    $message = CommunitySessionMessage::query()->where('body', 'Ik speel mee')->first();

    expect($message)->not->toBeNull()
        ->and($message->is_system)->toBeFalse()
        ->and(app(SessionChatService::class)->present($message)['is_system'])->toBeFalse();
});

test('member send stores a normal bubble message', function () {
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
        ]), ['body' => 'Hallo allen'])
        ->assertRedirect();

    $message = CommunitySessionMessage::query()->where('body', 'Hallo allen')->first();

    expect($message)->not->toBeNull()
        ->and($message->is_system)->toBeFalse()
        ->and(app(SessionChatService::class)->present($message)['is_system'])->toBeFalse();
});

test('authorSendsAsSystem is true only for for_chat_only seats', function () {
    $chat = app(SessionChatService::class);
    $staff = User::factory()->admin()->create();

    expect($chat->authorSendsAsSystem($this->session, $staff))->toBeFalse();

    CommunitySessionParticipant::factory()->forChatOnly()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $staff->id,
    ]);

    expect($chat->authorSendsAsSystem($this->session->fresh(), $staff))->toBeTrue();
});
