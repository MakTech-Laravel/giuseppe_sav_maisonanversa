<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\SessionParticipantStatus;
use App\Events\SessionMessageCreated;
use App\Models\CommunitySession;
use App\Models\CommunitySessionMessage;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();

    $this->host = User::factory()->create();
    $this->session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'starts_at' => now()->addDays(2),
        'capacity' => 2,
    ]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $this->host->id,
        'status' => SessionParticipantStatus::Confirmed,
        'for_chat_only' => false,
    ]);
});

function adminStoreMessageUrl(CommunitySession $session): string
{
    return route('admin.chat.messages.store', [
        'locale' => 'nl',
        'communitySession' => $session->id,
    ]);
}

test('staff without a seat force-join for chat only and can send', function () {
    Event::fake([SessionMessageCreated::class]);

    $player = User::factory()->create();
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $player->id,
        'status' => SessionParticipantStatus::Confirmed,
        'for_chat_only' => false,
    ]);

    expect($this->session->fresh()->isFull())->toBeTrue()
        ->and($this->session->fresh()->participantsCount())->toBe(2);

    $this->actingAs($this->admin)
        ->post(adminStoreMessageUrl($this->session), ['body' => 'Staff hier'])
        ->assertRedirect();

    $seat = CommunitySessionParticipant::query()
        ->where('community_session_id', $this->session->id)
        ->where('user_id', $this->admin->id)
        ->first();

    expect($seat)->not->toBeNull()
        ->and($seat->status)->toBe(SessionParticipantStatus::Confirmed)
        ->and($seat->for_chat_only)->toBeTrue()
        ->and($this->session->fresh()->participantsCount())->toBe(2)
        ->and($this->session->fresh()->isFull())->toBeTrue()
        ->and(CommunitySessionMessage::query()->where('body', 'Staff hier')->exists())->toBeTrue();

    Event::assertDispatched(SessionMessageCreated::class);
});

test('staff already confirmed as a player keep for_chat_only false when sending', function () {
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $this->admin->id,
        'status' => SessionParticipantStatus::Confirmed,
        'for_chat_only' => false,
    ]);

    $this->actingAs($this->admin)
        ->post(adminStoreMessageUrl($this->session), ['body' => 'Ik speel mee'])
        ->assertRedirect();

    $seat = CommunitySessionParticipant::query()
        ->where('community_session_id', $this->session->id)
        ->where('user_id', $this->admin->id)
        ->first();

    expect($seat->for_chat_only)->toBeFalse()
        ->and(CommunitySessionMessage::query()->where('body', 'Ik speel mee')->exists())->toBeTrue();
});

test('staff cannot send once the session has ended', function () {
    $this->session->update(['starts_at' => now()->subHours(3)]);

    // Super-admin Gate::before authorizes the FormRequest; sendAsStaff still
    // refuses closed sessions via validation.
    $this->actingAs($this->admin)
        ->from(route('admin.chat.show', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
        ]))
        ->post(adminStoreMessageUrl($this->session), ['body' => 'Te laat'])
        ->assertRedirect()
        ->assertSessionHasErrors('body');

    expect(CommunitySessionMessage::query()->count())->toBe(0)
        ->and(
            CommunitySessionParticipant::query()
                ->where('user_id', $this->admin->id)
                ->exists(),
        )->toBeFalse();
});

test('staff cannot send when the session is cancelled', function () {
    $this->session->update(['cancelled_at' => now()]);

    $this->actingAs($this->admin)
        ->from(route('admin.chat.show', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
        ]))
        ->post(adminStoreMessageUrl($this->session), ['body' => 'Geannuleerd'])
        ->assertRedirect()
        ->assertSessionHasErrors('body');

    expect(CommunitySessionMessage::query()->count())->toBe(0);
});

test('staff without sessions.manage cannot store chat messages', function () {
    config(['maison.admin_type_grants_all_permissions' => false]);

    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo(PermissionEnum::DASHBOARD_VIEW->value);

    $this->actingAs($staff)
        ->post(adminStoreMessageUrl($this->session), ['body' => 'Nee'])
        ->assertForbidden();

    expect(CommunitySessionMessage::query()->count())->toBe(0);
});

test('for_chat_only seats are excluded from the session feed player list', function () {
    CommunitySessionParticipant::factory()->forChatOnly()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.chat.show', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activeSession.participants_count', 1)
            ->where('activeSession.players', fn ($players) => collect($players)
                ->pluck('id')
                ->doesntContain($this->admin->id)));
});
