<?php

use App\Enums\NotificationType;
use App\Enums\RoleEnum;
use App\Enums\SessionNotice;
use App\Mail\SessionNoticeMail;
use App\Models\AppNotification;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

function memberInboxRow(User $user, array $notification = [], array $row = []): UserNotification
{
    return UserNotification::factory()->create([
        'user_id' => $user->id,
        'app_notification_id' => AppNotification::factory()->create($notification)->id,
        ...$row,
    ]);
}

test('session join request mails the host and adds an in-app notification', function () {
    Mail::fake();
    Queue::fake();

    $host = User::factory()->create();
    $host->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    $guest = User::factory()->create(['name' => 'Guest Member']);
    $guest->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $session = CommunitySession::factory()->create([
        'host_id' => $host->id,
        'capacity' => 4,
        'starts_at' => now()->addDay(),
    ]);

    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $host->id,
    ]);

    $this->actingAs($guest)
        ->post(route('community.sessions.join', ['locale' => 'nl', 'communitySession' => $session->id]))
        ->assertRedirect();

    Mail::assertQueued(SessionNoticeMail::class, function (SessionNoticeMail $mail) use ($host, $guest): bool {
        return $mail->hasTo($host->email)
            && $mail->subjectName === $guest->name
            && $mail->notice === SessionNotice::JoinRequested;
    });

    $row = $host->userNotifications()->with('notification')->first();

    expect($row)->not->toBeNull()
        ->and($row->notification->type)->toBe(NotificationType::Community)
        ->and($row->notification->message)->toContain('Guest Member')
        ->and($row->notification->data['session_id'])->toBe($session->id);
});

test('members can list their notifications with an unread count', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    memberInboxRow($user, ['title' => 'Een']);
    memberInboxRow($user, ['title' => 'Twee'], ['read_at' => now()]);
    memberInboxRow(User::factory()->create());

    $this->actingAs($user)
        ->get(localized('member.notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/notifications/index')
            ->where('unreadCount', 1)
            ->where('filters.status', 'all')
            ->has('rows.data', 2)
            ->has('rows.data.0', fn (Assert $row) => $row
                ->hasAll(['id', 'title', 'message', 'read', 'href', 'created_at_human'])
                ->etc()));
});

test('the member inbox filters by status', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    memberInboxRow($user, ['title' => 'Nieuw']);
    memberInboxRow($user, ['title' => 'Oud'], ['read_at' => now()]);

    $this->actingAs($user)
        ->get(localized('member.notifications.index', ['status' => 'unread']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', 'unread')
            ->has('rows.data', 1)
            ->where('rows.data.0.title', 'Nieuw'));

    $this->actingAs($user)
        ->get(localized('member.notifications.index', ['status' => 'read']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.title', 'Oud'));
});

test('guests are redirected away from the member inbox', function () {
    $this->get(localized('member.notifications.index'))
        ->assertRedirect();
});

test('opening a notification shows it and marks it as read', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $row = memberInboxRow($user, ['title' => 'Detail']);

    $this->actingAs($user)
        ->get(localized('member.notifications.show', ['userNotification' => $row->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/notifications/show')
            ->where('notification.id', $row->id)
            ->where('notification.title', 'Detail')
            ->where('notification.read', true));

    expect($row->fresh()->read_at)->not->toBeNull();
});

test('members cannot open someone else\'s notification', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $foreign = memberInboxRow(User::factory()->create());

    $this->actingAs($user)
        ->get(localized('member.notifications.show', ['userNotification' => $foreign->id]))
        ->assertNotFound();

    expect($foreign->fresh()->read_at)->toBeNull();
});

test('members can delete a notification from their inbox', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $row = memberInboxRow($user);

    $this->actingAs($user)
        ->from(localized('member.notifications.show', ['userNotification' => $row->id]))
        ->delete(localized('member.notifications.destroy', ['userNotification' => $row->id]))
        ->assertRedirect(localized('member.notifications.index'));

    expect(UserNotification::withTrashed()->find($row->id)->trashed())->toBeTrue();
});

test('members cannot delete someone else\'s notification', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $foreign = memberInboxRow(User::factory()->create());

    $this->actingAs($user)
        ->delete(localized('member.notifications.destroy', ['userNotification' => $foreign->id]))
        ->assertNotFound();

    expect($foreign->fresh()->trashed())->toBeFalse();
});

test('guests get no notifications on public pages', function () {
    $this->get(localized('maison.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('notifications', null));
});

test('signed-in members get their notifications on public pages', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    memberInboxRow($user);

    $this->actingAs($user)
        ->get(localized('maison.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.unreadCount', 1)
            ->has('notifications.preview', 1));
});

test('members can mark a notification as read', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $row = memberInboxRow($user);

    $this->actingAs($user)
        ->post(localized('member.notifications.read', ['notification' => $row->id]))
        ->assertRedirect();

    expect($row->fresh()->read_at)->not->toBeNull();
});

test('members cannot mark someone else\'s notification as read', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $foreign = memberInboxRow(User::factory()->create());

    $this->actingAs($user)
        ->post(localized('member.notifications.read', ['notification' => $foreign->id]))
        ->assertNotFound();

    expect($foreign->fresh()->read_at)->toBeNull();
});

test('members can mark all notifications as read', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    memberInboxRow($user);
    memberInboxRow($user);

    $this->actingAs($user)
        ->post(localized('member.notifications.read-all'))
        ->assertRedirect();

    expect($user->userNotifications()->unread()->count())->toBe(0);
});
