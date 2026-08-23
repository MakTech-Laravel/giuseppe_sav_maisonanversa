<?php

use App\Enums\RoleEnum;
use App\Models\CommunityPost;
use App\Models\CommunityPostHide;
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

test('members can hide a post from their own feed only', function () {
    $member = User::factory()->create();
    $member->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    $other = User::factory()->create();
    $other->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $post = CommunityPost::factory()->create(['content' => 'Visible post']);

    $this->actingAs($member)
        ->post(route('community.posts.hide', ['locale' => 'nl', 'communityPost' => $post->id]))
        ->assertRedirect();

    expect($post->fresh()->status)->toBe('published')
        ->and(CommunityPostHide::query()->where([
            'user_id' => $member->id,
            'community_post_id' => $post->id,
        ])->exists())->toBeTrue();

    $this->actingAs($member)
        ->get(route('maison.community', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('posts.data', 0));

    $this->actingAs($other)
        ->get(route('maison.community', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('posts.data', 1));
});

test('admin hidden posts stay on the authors wall but disappear for others', function () {
    $author = User::factory()->create();
    $author->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    $viewer = User::factory()->create();
    $viewer->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $post = CommunityPost::factory()->create([
        'author_id' => $author->id,
        'content' => 'Admin hidden post',
        'status' => 'hidden',
        'hidden_at' => now(),
    ]);

    $this->actingAs($viewer)
        ->get(route('maison.community', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('posts.data', 0));

    $this->actingAs($author)
        ->get(route('maison.community', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.content', 'Admin hidden post')
        );
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

test('posting a comment stores translations before the feed reloads', function () {
    fakeDeepLTranslations();

    $author = User::factory()->create();
    $author->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    $post = CommunityPost::factory()->create(['author_id' => $author->id]);

    $this->actingAs($author)
        ->post(route('community.posts.comments.store', [
            'locale' => 'en',
            'communityPost' => $post->id,
        ]), [
            'body' => 'Mooi bericht',
        ])
        ->assertRedirect();

    $comment = $post->fresh()->comments()->first();

    expect($comment)->not->toBeNull()
        ->and($comment->translations()->count())->toBe(3);

    $this->actingAs($author)
        ->get(route('maison.community', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('posts.data.0.comments.0.body', 'EN Mooi bericht')
        );
});
