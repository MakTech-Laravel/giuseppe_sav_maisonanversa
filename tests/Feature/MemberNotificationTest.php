<?php

use App\Enums\RoleEnum;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Notifications\SessionJoinedNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('session join creates a database notification for the host', function () {
    Notification::fake();

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
            && in_array('database', $notification->via($guest), true);
    });
});

test('members can mark a notification as read', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $user->notifyNow(new SessionJoinedNotification(
        CommunitySession::factory()->create([
            'host_id' => $user->id,
            'starts_at' => now()->addDay(),
        ]),
        'Another Member',
    ));

    $notification = $user->fresh()->unreadNotifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->read_at)->toBeNull();

    $this->actingAs($user)
        ->post(localized('member.notifications.read', ['notification' => $notification->id]))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});
