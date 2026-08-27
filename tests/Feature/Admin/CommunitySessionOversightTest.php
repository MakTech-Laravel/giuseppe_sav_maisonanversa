<?php

use App\Enums\RoleEnum;
use App\Enums\SessionSport;
use App\Models\Club;
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
});

test('staff see active, ended and cancelled sessions in one list', function () {
    CommunitySession::factory()->create();
    CommunitySession::factory()->past()->create();
    CommunitySession::factory()->cancelled()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.community-sessions.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/community-sessions/index')
            ->has('sessions.data', 3)
        );
});

test('staff can filter sessions by lifecycle state', function () {
    CommunitySession::factory()->create();
    CommunitySession::factory()->past()->create();
    CommunitySession::factory()->cancelled()->create();

    $lifecycles = ['active' => 'active', 'ended' => 'ended', 'cancelled' => 'cancelled'];

    foreach ($lifecycles as $filter => $expected) {
        $this->actingAs($this->admin)
            ->get(route('admin.community-sessions.index', ['locale' => 'nl', 'status' => $filter]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('sessions.data', 1)
                ->where('sessions.data.0.lifecycle', $expected)
            );
    }
});

test('staff can filter sessions by sport and club', function () {
    $club = Club::factory()->create();

    CommunitySession::factory()->tennis()->create(['club_id' => $club->id]);
    CommunitySession::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.community-sessions.index', [
            'locale' => 'nl',
            'sport' => SessionSport::Tennis->value,
            'club_id' => $club->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sessions.data', 1)
            ->where('sessions.data.0.club_id', $club->id)
        );
});

test('the session detail page lists every player', function () {
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

    $this->actingAs($this->admin)
        ->get(route('admin.community-sessions.show', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/community-sessions/show')
            ->has('session.players', 2)
            ->where('session.players.0.is_host', true)
        );
});

test('staff can remove a player from any session', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $session = CommunitySession::factory()->create(['host_id' => $host->id]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $guest->id,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.community-sessions.show', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->delete(route('admin.community-sessions.participants.destroy', [
            'locale' => 'nl',
            'communitySession' => $session->id,
            'user' => $guest->id,
        ]))
        ->assertRedirect();

    expect($session->participants()->where('user_id', $guest->id)->exists())->toBeFalse();
});

test('staff cannot remove the host from their own session', function () {
    $host = User::factory()->create();
    $session = CommunitySession::factory()->create(['host_id' => $host->id]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $host->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.community-sessions.participants.destroy', [
            'locale' => 'nl',
            'communitySession' => $session->id,
            'user' => $host->id,
        ]))
        ->assertStatus(422);
});

test('staff can cancel a session', function () {
    $session = CommunitySession::factory()->create();

    $this->actingAs($this->admin)
        ->from(route('admin.community-sessions.show', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->patch(route('admin.community-sessions.cancel', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertRedirect();

    expect($session->fresh()->isCancelled())->toBeTrue();
});

test('a cancelled session leaves the member open tab but stays in admin', function () {
    $member = User::factory()->create();
    $session = CommunitySession::factory()->create();

    $this->actingAs($this->admin)
        ->from(route('admin.community-sessions.index', ['locale' => 'nl']))
        ->patch(route('admin.community-sessions.cancel', ['locale' => 'nl', 'communitySession' => $session->id]));

    $this->actingAs($member)
        ->get(route('community.sessions.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('counts.open', 0));

    $this->actingAs($this->admin)
        ->get(route('admin.community-sessions.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('sessions.data', 1));
});

test('members cannot reach the admin session dashboard', function () {
    $member = User::factory()->create();

    $this->actingAs($member)
        ->get(route('admin.community-sessions.index', ['locale' => 'nl']))
        ->assertForbidden();
});
