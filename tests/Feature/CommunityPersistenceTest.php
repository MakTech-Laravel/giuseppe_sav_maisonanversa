<?php

use App\Enums\RoleEnum;
use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\CommunityPost;
use App\Models\CommunitySession;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('founding circle members can publish a community post', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $this->actingAs($user)
        ->post(route('community.posts.store', ['locale' => 'nl']), [
            'content' => 'Hello from the circle.',
        ])
        ->assertRedirect();

    expect(CommunityPost::query()->where('content', 'Hello from the circle.')->exists())->toBeTrue();
});

test('verified members can create and join a session', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $club = Club::factory()->create();

    $this->actingAs($host)
        ->post(route('community.sessions.store', ['locale' => 'nl']), [
            'sport' => SessionSport::Padel->value,
            'club_id' => $club->id,
            'starts_at' => now()->addDay()->toDateTimeString(),
            'duration_minutes' => 90,
            'court_status' => SessionCourtStatus::Booked->value,
            'level' => SessionLevel::OpenToAll->value,
            'gender' => SessionGender::Everyone->value,
            'capacity' => 2,
        ])
        ->assertRedirect();

    $session = CommunitySession::query()->first();

    $this->actingAs($guest)
        ->post(route('community.sessions.join', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertRedirect();

    expect($session->participants()->count())->toBe(2);
});

test('staff can publish an official community post', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(RoleEnum::ADMIN->value);
    $staff->syncTypeFromRoles();

    $this->actingAs($staff)
        ->post(route('admin.community.official', ['locale' => 'nl']), [
            'content' => 'House announcement.',
        ])
        ->assertRedirect();

    expect(CommunityPost::query()->where('is_official', true)->where('content', 'House announcement.')->exists())->toBeTrue();
});
