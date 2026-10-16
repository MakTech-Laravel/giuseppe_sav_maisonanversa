<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\SessionParticipantStatus;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();

    $this->host = User::factory()->create();
    $this->session = CommunitySession::factory()->create(['host_id' => $this->host->id]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $this->host->id,
        'status' => SessionParticipantStatus::Confirmed,
    ]);
});

function adminChatIndexUrl(): string
{
    return route('admin.chat.index', ['locale' => 'nl']);
}

function adminChatShowUrl(CommunitySession $session): string
{
    return route('admin.chat.show', ['locale' => 'nl', 'communitySession' => $session->id]);
}

test('staff with sessions.manage open the chat inbox', function () {
    $this->actingAs($this->admin)
        ->get(adminChatIndexUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/chat')
            ->where('activeSession', null)
            ->has('sessions', 1)
            ->where('messages', []));
});

test('staff open a session chat as read-only', function () {
    $this->actingAs($this->admin)
        ->get(adminChatShowUrl($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/chat')
            ->where('activeSession.id', (string) $this->session->id)
            ->where('viewer.id', $this->admin->id)
            ->where('messages', []));
});

test('staff without sessions.manage cannot open chat', function () {
    config(['maison.admin_type_grants_all_permissions' => false]);

    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo(PermissionEnum::DASHBOARD_VIEW->value);

    $this->actingAs($staff)
        ->get(adminChatIndexUrl())
        ->assertForbidden();

    $this->actingAs($staff)
        ->get(adminChatShowUrl($this->session))
        ->assertForbidden();
});

test('guests are sent to log in', function () {
    $this->get(adminChatIndexUrl())->assertRedirect();
});
