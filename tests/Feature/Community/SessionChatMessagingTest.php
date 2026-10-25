<?php

use App\Enums\RoleEnum;
use App\Enums\SessionParticipantStatus;
use App\Events\SessionChatActivity;
use App\Events\SessionMessageCreated;
use App\Models\CommunitySession;
use App\Models\CommunitySessionChatRead;
use App\Models\CommunitySessionMessage;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Services\Community\SessionChatService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->host = User::factory()->create();
    $this->session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'starts_at' => now()->addDays(2),
    ]);

    seat($this->session, $this->host);
});

function seat(CommunitySession $session, User $user, SessionParticipantStatus $status = SessionParticipantStatus::Confirmed): void
{
    CommunitySessionParticipant::factory()->create([
        'community_session_id' => $session->id,
        'user_id' => $user->id,
        'status' => $status,
    ]);
}

function storeMessageUrl(CommunitySession $session): string
{
    return route('member.chat.messages.store', [
        'locale' => 'nl',
        'communitySession' => $session->id,
    ]);
}

function showChatUrl(CommunitySession $session): string
{
    return route('member.chat.show', [
        'locale' => 'nl',
        'communitySession' => $session->id,
    ]);
}

function readChatUrl(CommunitySession $session): string
{
    return route('member.chat.read', [
        'locale' => 'nl',
        'communitySession' => $session->id,
    ]);
}

test('a confirmed member can send a message while the session is open', function () {
    Event::fake([SessionMessageCreated::class]);

    $player = User::factory()->create();
    seat($this->session, $player);

    $this->actingAs($player)
        ->post(storeMessageUrl($this->session), ['body' => 'Tot zo op de baan'])
        ->assertRedirect();

    $message = CommunitySessionMessage::query()->first();

    expect($message)->not->toBeNull()
        ->and($message->body)->toBe('Tot zo op de baan')
        ->and($message->user_id)->toBe($player->id)
        ->and($message->community_session_id)->toBe($this->session->id);

    Event::assertDispatched(SessionMessageCreated::class, function (SessionMessageCreated $event) use ($message): bool {
        return $event->message->is($message)
            && $event->broadcastAs() === 'message.created'
            && $event->broadcastOn()[0]->name === 'private-community-session.'.$message->community_session_id.'.chat';
    });
});

test('the host can send a message with images', function () {
    Storage::fake('public');
    Event::fake([SessionMessageCreated::class]);

    $this->actingAs($this->host)
        ->post(storeMessageUrl($this->session), [
            'body' => 'Foto',
            'images' => [
                UploadedFile::fake()->image('court.jpg', 200, 200),
            ],
        ])
        ->assertRedirect();

    $message = CommunitySessionMessage::query()->with('attachments')->first();

    expect($message->attachments)->toHaveCount(1)
        ->and($message->attachments->first()->original_name)->toBe('court.jpg');

    Storage::disk('public')->assertExists($message->attachments->first()->path);
});

test('sending is forbidden once the post-end write window has closed', function () {
    $this->session->update([
        'starts_at' => now()->subHours(SessionChatService::WRITE_WINDOW_HOURS + 4),
    ]);

    $this->actingAs($this->host)
        ->post(storeMessageUrl($this->session), ['body' => 'Te laat'])
        ->assertForbidden();

    expect(CommunitySessionMessage::query()->count())->toBe(0);
});

test('sending is forbidden when the session is cancelled', function () {
    $this->session->update(['cancelled_at' => now()]);

    $this->actingAs($this->host)
        ->post(storeMessageUrl($this->session), ['body' => 'Nog iets'])
        ->assertForbidden();

    expect(CommunitySessionMessage::query()->count())->toBe(0);
});

test('pending players and outsiders cannot send', function (?SessionParticipantStatus $status) {
    $member = User::factory()->create();

    if ($status !== null) {
        seat($this->session, $member, $status);
    }

    $this->actingAs($member)
        ->post(storeMessageUrl($this->session), ['body' => 'Hallo'])
        ->assertForbidden();
})->with([
    'pending' => SessionParticipantStatus::Pending,
    'rejected' => SessionParticipantStatus::Rejected,
    'outsider' => null,
]);

test('the chat show page loads persisted messages and can_send', function () {
    $message = CommunitySessionMessage::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $this->host->id,
        'body' => 'Welkom',
    ]);

    $this->actingAs($this->host)
        ->get(showChatUrl($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/chat')
            ->where('can_send', true)
            ->where('messages_purge_at', null)
            ->has('messages', 1)
            ->where('messages.0.id', (string) $message->id)
            ->where('messages.0.body', 'Welkom'));
});

test('a closed session is read-only and exposes messages_purge_at', function () {
    $this->session->update([
        'starts_at' => now()->subHours(SessionChatService::WRITE_WINDOW_HOURS + 4),
    ]);
    $this->session->refresh();

    $purgeAt = app(SessionChatService::class)->messagesPurgeAt($this->session);

    $this->actingAs($this->host)
        ->get(showChatUrl($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/chat')
            ->where('can_send', false)
            ->where('messages_purge_at', $purgeAt?->toIso8601String()));
});

test('admins can authorize the session chat channel', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => 8080,
        'broadcasting.connections.reverb.options.scheme' => 'http',
        'broadcasting.connections.reverb.options.useTLS' => false,
    ]);

    require base_path('routes/channels.php');

    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    $this->actingAs($admin)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-community-session.'.$this->session->id.'.chat',
        ])
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test('confirmed players can authorize the session chat channel', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => 8080,
        'broadcasting.connections.reverb.options.scheme' => 'http',
        'broadcasting.connections.reverb.options.useTLS' => false,
    ]);

    require base_path('routes/channels.php');

    $this->actingAs($this->host)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-community-session.'.$this->session->id.'.chat',
        ])
        ->assertOk();
});

test('outsiders cannot authorize the session chat channel', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => 8080,
        'broadcasting.connections.reverb.options.scheme' => 'http',
        'broadcasting.connections.reverb.options.useTLS' => false,
    ]);

    require base_path('routes/channels.php');

    $this->actingAs(User::factory()->create())
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-community-session.'.$this->session->id.'.chat',
        ])
        ->assertForbidden();
});

test('the admin store route for session chat messages exists', function () {
    expect(collect(Route::getRoutes())->contains(
        fn ($route): bool => $route->getName() === 'admin.chat.messages.store',
    ))->toBeTrue();
});

test('sending a message bumps unread for other players and clears for the sender', function () {
    Event::fake([SessionMessageCreated::class, SessionChatActivity::class]);

    $player = User::factory()->create();
    seat($this->session, $player);

    $chat = app(SessionChatService::class);

    $this->actingAs($player)
        ->post(storeMessageUrl($this->session), ['body' => 'Hallo host'])
        ->assertRedirect();

    expect($chat->unreadCountFor($this->host, $this->session))->toBe(1)
        ->and($chat->unreadCountFor($player, $this->session))->toBe(0)
        ->and($chat->unreadTotal($this->host))->toBe(1);

    Event::assertDispatched(SessionChatActivity::class, function (SessionChatActivity $event): bool {
        return $event->userId === $this->host->id
            && $event->sessionId === $this->session->id
            && $event->unreadTotal === 1
            && $event->broadcastAs() === 'session.chat.activity';
    });
});

test('opening the chat marks messages as read', function () {
    $player = User::factory()->create();
    seat($this->session, $player);

    CommunitySessionMessage::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $player->id,
        'body' => 'Ongelezen',
    ]);

    $chat = app(SessionChatService::class);

    expect($chat->unreadCountFor($this->host, $this->session))->toBe(1);

    $this->actingAs($this->host)
        ->get(showChatUrl($this->session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('unread_counts.'.(string) $this->session->id, 0));

    expect($chat->unreadCountFor($this->host, $this->session))->toBe(0)
        ->and(CommunitySessionChatRead::query()
            ->where('user_id', $this->host->id)
            ->where('community_session_id', $this->session->id)
            ->exists())->toBeTrue();
});

test('the mark-read endpoint clears unread without loading the transcript', function () {
    $player = User::factory()->create();
    seat($this->session, $player);

    CommunitySessionMessage::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $player->id,
        'body' => 'Ping',
    ]);

    $this->actingAs($this->host)
        ->post(readChatUrl($this->session))
        ->assertRedirect();

    expect(app(SessionChatService::class)->unreadCountFor($this->host, $this->session))->toBe(0);
});

test('stored chat images expose a public storage url', function () {
    Storage::fake('public');
    Event::fake([SessionMessageCreated::class, SessionChatActivity::class]);

    $this->actingAs($this->host)
        ->post(storeMessageUrl($this->session), [
            'body' => 'Foto',
            'images' => [
                UploadedFile::fake()->image('court.png', 120, 120),
            ],
        ])
        ->assertRedirect();

    $attachment = CommunitySessionMessage::query()->with('attachments')->first()?->attachments->first();

    expect($attachment)->not->toBeNull()
        ->and(Storage::disk('public')->exists($attachment->path))->toBeTrue()
        ->and($attachment->url())->toContain('/storage/');
});

test('the public storage link resolves to storage/app/public', function () {
    $link = public_path('storage');
    $target = realpath(storage_path('app/public'));

    expect(file_exists($link))->toBeTrue()
        ->and($target)->not->toBeFalse()
        ->and(realpath($link))->toBe($target);
});
