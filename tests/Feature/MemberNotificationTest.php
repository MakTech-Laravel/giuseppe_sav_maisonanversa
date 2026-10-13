<?php

use App\Enums\NotificationType;
use App\Enums\RoleEnum;
use App\Models\AppNotification;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\SessionJoinedNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

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

test('session join mails the host and adds an in-app notification', function () {
    Notification::fake();
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

    Notification::assertSentTo($host, SessionJoinedNotification::class, function (SessionJoinedNotification $notification) use ($guest): bool {
        return $notification->memberName === $guest->name
            && $notification->via($guest) === ['mail'];
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

    $response = $this->actingAs($user)
        ->getJson(localized('member.notifications.index'))
        ->assertOk();

    $response->assertJsonPath('unread_count', 1);
    $response->assertJsonCount(2, 'data');
    expect($response->json('data.0'))
        ->toHaveKeys(['id', 'title', 'body', 'read_at', 'created_at']);
});

test('a guest cannot list another member\'s notifications', function () {
    $this->getJson(localized('member.notifications.index'))
        ->assertUnauthorized();
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
