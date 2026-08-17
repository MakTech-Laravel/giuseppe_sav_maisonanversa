<?php

use App\Enums\RoleEnum;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Notifications\ReportFiledNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('members can report a community post', function () {
    $author = User::factory()->create();
    $author->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    $reporter = User::factory()->create();
    $reporter->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $moderator = User::factory()->admin()->create();
    $moderator->assignRole(RoleEnum::ADMIN->value);
    $moderator->syncTypeFromRoles();

    $post = CommunityPost::factory()->create(['author_id' => $author->id]);

    Notification::fake();

    $this->actingAs($reporter)
        ->post(route('community.posts.report', ['locale' => 'nl', 'communityPost' => $post->id]), [
            'reason' => 'Inappropriate content in this post.',
        ])
        ->assertRedirect();

    expect(CommunityReport::query()->where([
        'community_post_id' => $post->id,
        'reporter_id' => $reporter->id,
        'status' => 'open',
    ])->exists())->toBeTrue();

    Notification::assertSentTo($moderator, ReportFiledNotification::class);
});

test('moderators can hide a community post', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(RoleEnum::ADMIN->value);
    $staff->syncTypeFromRoles();

    $post = CommunityPost::factory()->create();

    $this->actingAs($staff)
        ->post(route('community.posts.hide', ['locale' => 'nl', 'communityPost' => $post->id]))
        ->assertRedirect();

    expect($post->fresh()->status)->toBe('hidden')
        ->and($post->fresh()->hidden_at)->not->toBeNull();
});

test('founding circle members can leave a session', function () {
    $host = User::factory()->create();
    $host->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    $guest = User::factory()->create();
    $guest->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $session = CommunitySession::factory()->create(['host_id' => $host->id, 'capacity' => 4]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $host->id,
    ]);
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $guest->id,
    ]);

    $this->actingAs($guest)
        ->delete(route('community.sessions.leave', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertRedirect();

    expect(CommunitySessionParticipant::query()
        ->where('community_session_id', $session->id)
        ->where('user_id', $guest->id)
        ->exists())->toBeFalse();
});

test('staff can resolve and dismiss community reports', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(RoleEnum::ADMIN->value);
    $staff->syncTypeFromRoles();

    $report = CommunityReport::factory()->create(['status' => 'open']);

    $this->actingAs($staff)
        ->patch(route('admin.community.reports.resolve', [
            'locale' => 'nl',
            'communityReport' => $report->id,
        ]), [
            'status' => 'resolved',
        ])
        ->assertRedirect();

    expect($report->fresh()->status)->toBe('resolved');

    $open = CommunityReport::factory()->create(['status' => 'open']);

    $this->actingAs($staff)
        ->patch(route('admin.community.reports.resolve', [
            'locale' => 'nl',
            'communityReport' => $open->id,
        ]), [
            'status' => 'dismissed',
        ])
        ->assertRedirect();

    expect($open->fresh()->status)->toBe('dismissed');
});
